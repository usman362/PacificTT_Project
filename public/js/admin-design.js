
/* ── server calls ──────────────────────────────────────────────────── */
function pttPost(url, payload) {
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
    },
    body: JSON.stringify(payload)
  }).then(async r => {
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Request failed');
    return data;
  });
}

const pages=[...document.querySelectorAll('.page')];
document.querySelectorAll('#nav button[data-href]').forEach(b=>b.onclick=()=>{location.href=b.dataset.href});
document.querySelectorAll('#nav button[data-logout]').forEach(b=>b.onclick=()=>document.getElementById('logoutForm').submit());
document.querySelectorAll('#nav button[data-page]').forEach(b=>b.onclick=()=>{
 document.querySelectorAll('#nav button[data-page]').forEach(x=>x.classList.remove('active'));b.classList.add('active');
 pages.forEach(p=>p.classList.remove('active'));document.getElementById(b.dataset.page).classList.add('active');
 document.getElementById('pageTitle').textContent=b.textContent;window.scrollTo({top:0,behavior:'smooth'});
 if(b.dataset.page==='schedule') renderSchedule();
});

function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function renderSchedule(){
 const dateEl=document.getElementById('schedDate');
 const date=dateEl?.value || new Date().toISOString().slice(0,10);
 fetch(`${window.PTT.routes.schedule}?date=${date}`,{headers:{'Accept':'application/json'}})
  .then(r=>r.json())
  .then(d=>{
    const grid=document.getElementById('sessionGrid');
    if(d.closed){
      grid.innerHTML='<div class="instructor"><b>Closed</b><p class="note">No sessions run on this day.</p></div>';
    } else if(!d.capacity){
      grid.innerHTML='<div class="instructor"><b>No capacity</b><p class="note">No instructor is available on this date, so the public calendar shows zero seats.</p></div>';
    } else {
      const per=d.seats_per_instructor;
      // Each session's students are shared among the instructors covering it:
      // whole students, the remainder going to the first instructors. That is
      // how the Assistant screen shows an instructor's own group.
      const share={};
      d.sessions.forEach(s=>{
        const n=s.instructors.length, base=n?Math.floor(s.booked/n):0, rem=n?s.booked%n:0;
        s.instructors.forEach((inst,i)=>{ share[s.slot+'|'+inst.id]=Math.min(per,base+(i<rem?1:0)); });
      });
      const slotOf={}; d.sessions.forEach(s=>slotOf[s.slot]=s);
      grid.innerHTML=d.roster.map(i=>{
        const head=`<div class="instructor"><div class="insthead"><b>${esc(i.name)}</b>`+
          `<span class="pill ${i.available?'paid':'missing'}">${i.available?'AVAILABLE':'UNAVAILABLE'}</span></div>`;
        if(!i.available) return head+`<p class="note">Not scheduled on this date — adds no seats.</p></div>`;
        return head+i.slots.map(name=>{
          const s=slotOf[name], b=share[name+'|'+i.id]||0, avail=Math.max(0,per-b);
          const program=(s.tiers&&s.tiers.length)?s.tiers.join(' / '):'No students booked';
          return `<div class="session"><div class="sessiontop"><b>${esc(s.slot)}</b><span><b>${b}</b> students · ${avail} open</span></div>`+
            `<div class="note" style="margin-top:4px">${esc(program)}</div>`+
            `<div class="bar"><div class="fill" style="width:${Math.min(100,b/per*100)}%"></div></div></div>`;
        }).join('')+'</div>';
      }).join('');
    }
    // Keep the instructor count honest: it states how many are actually
    // available on the chosen date rather than whatever was last picked. This
    // runs for closed days too, where the honest answer is zero.
    const sel=document.getElementById('instructorCount');
    if(sel){
      const n=d.available_count;
      if(!sel.querySelector(`option[value="${n}"]`)){
        const o=document.createElement('option');
        o.value=n; o.textContent=`${n} instructor${n===1?'':'s'}`;
        sel.appendChild(o);
      }
      sel.value=String(n);
    }
    const cap=document.getElementById('todayCapacity'), rem=document.getElementById('todayRemaining');
    if(cap) cap.textContent=d.capacity;
    if(rem) rem.textContent=Math.max(0,d.capacity-d.booked);
    const sm=document.getElementById('seatMetric'), om=document.getElementById('occMetric');
    if(sm) sm.textContent=`${d.booked} / ${d.capacity} booked`;
    if(om) om.textContent=(d.capacity?(d.booked/d.capacity*100):0).toFixed(1)+'%';
    renderDash(d);
  })
  .catch(()=>{});
}
function renderDash(d){
 const body=document.getElementById('dashSessions'); if(!body||!d) return;
 body.innerHTML=d.sessions.map(s=>
  `<tr><td>${s.slot}</td><td>${s.instructors.length} available</td><td>${s.booked}</td><td>${s.available}</td>`+
  `<td><span class="pill ${s.capacity===0?'missing':(s.available===0?'due':'paid')}">${s.capacity===0?'CLOSED':(s.available===0?'FULL':'OPEN')}</span></td></tr>`
 ).join('');
}
renderSchedule();

function selectCert(n,no,c,d,i){
 document.getElementById('certName').textContent=n;
 document.getElementById('recName').textContent=n;
 document.getElementById('certNo').textContent=no;
 document.getElementById('recNo').textContent=no;
 document.getElementById('certCourse').textContent=c;
 document.getElementById('recCourse').textContent=c;
 document.getElementById('certDate').textContent=d;
 document.getElementById('recDate').textContent=d;
 document.getElementById('certInstructor').textContent=i;
}
function filterCertRows(){
 const q=(document.getElementById('certSearch')?.value||'').toLowerCase();
 document.querySelectorAll('#certRows tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none');
}
document.getElementById('certSearch')?.addEventListener('input',filterCertRows);


const weekDays = window.PTT?.weekDays || ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
let instructors = (window.PTT?.instructors || []).map(i => ({...i}));
function buildAvailabilityEditor(data){
 const box=document.getElementById('availabilityEditor'); if(!box)return;
 box.innerHTML=weekDays.map(d=>{const a=data?.[d]||{on:true,start:'08:00',end:'20:00'};return `<div class="avail-grid"><label style="margin:0;text-transform:none"><input type="checkbox" id="on_${d}" ${a.on?'checked':''}> ${d.slice(0,3)}</label><input type="time" id="start_${d}" value="${a.start}"><input type="time" id="end_${d}" value="${a.end}"></div>`}).join('');
}
function renderInstructors(){
 const body=document.getElementById('instructorRows'); if(!body)return;
 const per=window.PTT?.seatsPerInstructor||8, slots=(window.PTT?.sessionSlots||[]).length||3;
 // An instructor adds seats only when active AND available on at least one day.
 const adds=i=>i.status==='Active'&&weekDays.some(d=>i.avail[d]?.on);
 body.innerHTML=instructors.map(i=>{
  const days=weekDays.filter(d=>i.avail[d]?.on).length, first=weekDays.find(d=>i.avail[d]?.on);
  return `<tr><td><b>${esc(i.name)}</b><div class="note">${esc(i.course)}</div></td><td><span class="pill ${i.status==='Active'?'paid':'missing'}">${esc(i.status.toUpperCase())}</span></td><td>$${Number(i.rate).toLocaleString()}/day</td><td>${days} days/week<br><span class="note">${days?esc(i.avail[first].start+'–'+i.avail[first].end):'Unavailable'}</span></td><td>${adds(i)?per*slots:0} seats/day max</td><td><div class="inst-actions"><button class="btn light" onclick="editInstructor(${Number(i.id)})">Edit</button><button class="btn danger" onclick="deleteInstructor(${Number(i.id)})">Delete</button></div></td></tr>`;
 }).join('');
 const active=instructors.filter(adds).length;
 document.getElementById('activeInstructorCount').textContent=active;
 document.getElementById('instSeatCapacity').textContent=active*per;
 document.getElementById('instDayCapacity').textContent=active*per*slots;
}
function newInstructor(){
 document.getElementById('instId').value='';document.getElementById('instName').value='';document.getElementById('instEmail').value='';document.getElementById('instPhone').value='';document.getElementById('instRate').value=1200;document.getElementById('instStatus').value='Active';document.getElementById('instCourse').value='Core + Advanced';document.getElementById('instFormTitle').textContent='Add Instructor';buildAvailabilityEditor();
}
function editInstructor(id){
 const i=instructors.find(x=>x.id===id); if(!i)return;
 document.getElementById('instId').value=i.id;document.getElementById('instName').value=i.name;document.getElementById('instEmail').value=i.email;document.getElementById('instPhone').value=i.phone;document.getElementById('instRate').value=i.rate;document.getElementById('instStatus').value=i.status;document.getElementById('instCourse').value=i.course;document.getElementById('instFormTitle').textContent='Edit Instructor';buildAvailabilityEditor(i.avail);window.scrollTo({top:document.body.scrollHeight,behavior:'smooth'});
}
function saveInstructor(){
 const name=document.getElementById('instName').value.trim();if(!name){alert('Instructor name is required.');return}
 const avail={};weekDays.forEach(d=>avail[d]={on:document.getElementById('on_'+d).checked,start:document.getElementById('start_'+d).value,end:document.getElementById('end_'+d).value});
 const obj={id:+document.getElementById('instId').value||null,name,email:document.getElementById('instEmail').value,phone:document.getElementById('instPhone').value,rate:+document.getElementById('instRate').value,status:document.getElementById('instStatus').value,course:document.getElementById('instCourse').value,avail};
 pttPost(window.PTT.routes.saveInstructor, obj)
   .then(res => { obj.id = res.id;
     const idx = instructors.findIndex(x => x.id === obj.id);
     if (idx >= 0) instructors[idx] = obj; else instructors.push(obj);
     renderInstructors(); newInstructor(); renderSchedule();
   })
   .catch(e => alert(e.message));
}
function deleteInstructor(id){
  if (!confirm('Remove this instructor? Their availability no longer counts toward calendar capacity.')) return;
  pttPost(window.PTT.routes.deleteInstructor, {id})
    .then(() => { instructors = instructors.filter(x => x.id !== id); renderInstructors(); renderSchedule(); })
    .catch(e => alert(e.message));
}
buildAvailabilityEditor();renderInstructors();


function openWaiver(id){
 const w = (window.PTT?.waivers || {})[id];
 if(!w) return;
 currentWaiverId = id;
 document.getElementById('wvEnrollment').textContent = w.enrollment || '—';
 document.getElementById('wvStudent').textContent    = w.student || '—';
 document.getElementById('wvProgram').textContent    = w.program || '—';
 document.getElementById('repName').value      = w.staffName || '';
 document.getElementById('repSigned').value    = w.staffName || '';
 document.getElementById('repSignature').textContent = w.staffName || 'Click to apply staff signature';
 document.getElementById('repSignature').classList.toggle('signed', w.executed);
 document.getElementById('repDate').value      = w.staffDate || '';
 document.getElementById('staffBox').classList.toggle('locked', w.executed);
 document.getElementById('acceptComplete').style.display = w.executed ? 'block' : 'none';
 if(w.executed) document.getElementById('acceptSummary').textContent =
   `Accepted by ${w.staffName} on ${w.staffDate}.`;
 window.scrollTo({top:document.body.scrollHeight,behavior:'smooth'});
}
let currentWaiverId = null;
function signRep(){
 const name=document.getElementById('repName').value.trim();
 if(!name){alert('Enter Representative Name before signing.');return}
 document.getElementById('repSigned').value=name;
 document.getElementById('repSignature').textContent=name;
 document.getElementById('repSignature').classList.add('signed');
 if(!document.getElementById('repDate').value){
   const d=new Date();document.getElementById('repDate').value=d.toISOString().slice(0,10);
 }
}
function completeAcceptance(){
 const n=document.getElementById('repName').value.trim(),
       sg=document.getElementById('repSigned').value,
       d=document.getElementById('repDate').value;
 if(!n||!sg||!d){alert('Representative Name, Representative Signature, and Date are required.');return}
 if(!currentWaiverId){alert('Open a waiver from the table first.');return}
 pttPost(window.PTT.routes.acceptWaiver,{id:currentWaiverId,staff:n,signature:sg,date:d})
  .then(()=>{
    document.getElementById('staffBox').classList.add('locked');
    document.getElementById('acceptComplete').style.display='block';
    document.getElementById('acceptSummary').textContent=`Accepted by ${n} on ${d}.`;
    if(window.PTT.waivers[currentWaiverId]){
      Object.assign(window.PTT.waivers[currentWaiverId],{executed:true,staffName:n,staffDate:d});
    }
    const due=document.getElementById('waiverDue'), ex=document.getElementById('waiverExecuted');
    if(due) due.textContent=Math.max(0,+due.textContent-1);
    if(ex)  ex.textContent=+ex.textContent+1;
  })
  .catch(e=>alert(e.message));
}
function filterWaivers(){
 const q=(document.getElementById('waiverSearch')?.value||'').toLowerCase();
 document.querySelectorAll('#waiverRows tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none');
}
document.getElementById('waiverSearch')?.addEventListener('input',filterWaivers);


function showMerchantFields(){
 const m=document.getElementById('merchantSelect')?.value;
 if(!m)return;
 document.getElementById('stripeFields').style.display=m==='stripe'?'block':'none';
 document.getElementById('paypalFields').style.display=m==='paypal'?'block':'none';
 document.getElementById('zelleFields').style.display=m==='zelle'?'block':'none';
 document.getElementById('otherFields').style.display=m==='other'?'block':'none';
}


function verifyZelle(id){
 if(!confirm('Confirm Zelle funds were received and verified in-house?')) return;
 pttPost(window.PTT.routes.verifyZelle, {id})
  .then(()=>{ const row=document.querySelector(`tr[data-payment="${id}"] .pill`);
    if(row){ row.textContent='VERIFIED / PAID'; row.className='pill paid'; }
    alert('Zelle payment verified and recorded against your staff account.');
  })
  .catch(e=>alert(e.message));
}

let leads = (window.PTT?.leads || []).map(l => ({...l}));
function statusPill(s){let c=s==='Paid'?'paid':s==='New'?'due':s==='Lost'?'missing':'';return `<span class="pill ${c}">${esc(String(s).toUpperCase())}</span>`}
function renderLeads(){
 const b=document.getElementById('leadRows');if(!b)return;
 b.innerHTML=leads.map(l=>`<tr><td><b>${esc(l.name)}</b><div class="note">LD-${String(l.id).padStart(6,'0')}</div></td><td>${esc(l.phone)}<div class="note">${esc(l.email)}</div></td><td>${esc(l.program)}</td><td>${esc(l.date||'Flexible')}<div class="note">${esc(l.session)}</div></td><td>${esc(l.source)}</td><td>${statusPill(l.status)}</td><td>${esc(l.follow)}</td><td><button class="btn light" onclick="editLead(${Number(l.id)})">Open</button></td></tr>`).join('');
 filterLeadRows();
}
function newLead(){
 ['leadId','leadName','leadPhone','leadEmail','leadDate','leadFollow','leadNotes'].forEach(x=>{let e=document.getElementById(x);if(e)e.value=''});
 document.getElementById('leadStatus').value='New';document.getElementById('leadFormTitle').textContent='Add Lead';
}
function editLead(id){
 const l=leads.find(x=>x.id===id);if(!l)return;
 leadId.value=l.id;leadName.value=l.name;leadPhone.value=l.phone;leadEmail.value=l.email;leadProgram.value=l.program;leadDate.value=l.date;leadSession.value=l.session;leadSource.value=l.source;leadStatus.value=l.status;leadElectrical.value=l.electrical;leadPLC.value=l.plc;leadNotes.value=l.notes;leadFormTitle.textContent='Lead Details — '+l.name;
}
function saveLead(){
 const name=leadName.value.trim(),phone=leadPhone.value.trim();if(!name||!phone){alert('Name and phone are required.');return}
 // A new lead has no id yet; the server assigns one.
 const id=+leadId.value||null;
 const l={id,name,phone,email:leadEmail.value,program:leadProgram.value,date:leadDate.value,session:leadSession.value,source:leadSource.value,status:leadStatus.value,follow:leadFollow.value||'Not scheduled',electrical:leadElectrical.value,plc:leadPLC.value,notes:leadNotes.value};
 pttPost(window.PTT.routes.saveLead, l)
   .then(res => { l.id = res.id;
     const ix = leads.findIndex(x => x.id === l.id);
     if (ix >= 0) leads[ix] = l; else leads.unshift(l);
     renderLeads(); newLead();
   })
   .catch(e => alert(e.message));
}
function markContacted(){leadStatus.value='Contacted';saveLead()}
function startEnrollmentFromLead(){
 const l=leads.find(x=>x.id===+leadId.value);
 if(!l){alert('Open or create a lead first.');return}
 // Carry the lead's details into a new enrollment; the lead is marked
 // "Enrollment Started" only once that enrollment actually exists.
 newEnrollment({
   name:l.name, phone:l.phone, email:l.email, electrical:l.electrical, plc:l.plc,
   program_id:l.program_id||'', session:l.session==='Flexible'?'':l.session, date:l.date||''
 }, () => pttPost(window.PTT.routes.saveLead, {...l, status:'Enrollment Started'}));
}
function filterLeadRows(){
 const q=(document.getElementById('leadSearch')?.value||'').toLowerCase(),s=document.getElementById('leadStatusFilter')?.value||'';
 document.querySelectorAll('#leadRows tr').forEach(r=>{const text=r.innerText.toLowerCase();r.style.display=(text.includes(q)&&(!s||text.includes(s.toLowerCase())))?'':'none'});
}
document.getElementById('leadSearch')?.addEventListener('input',filterLeadRows);
renderLeads();
