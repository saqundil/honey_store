(() => {
  'use strict';
  const columns = Object.fromEntries(REPORT_DATA.columns.map(column => [column.column_key, column]));
  const calculated = REPORT_DATA.columns.filter(column => column.formula);
  function calculateRow(row) {
    const values = {};
    row.querySelectorAll('[data-column-key]').forEach(cell => { const input=cell.querySelector('input'); if(input) values[cell.dataset.columnKey]=input.value===''?null:Number(input.value); });
    let changed=true, guard=0;
    while(changed && guard++<calculated.length+1){changed=false;calculated.forEach(column=>{if(values[column.column_key]!=null)return;const source=column.formula.sources.map(key=>values[key]);if(source.some(value=>value==null))return;const sum=source.reduce((total,value)=>total+Number(value),0);values[column.column_key]=column.formula.type==='AVERAGE'?sum/source.length:column.formula.type==='PERCENTAGE'?sum/Number(column.formula.base||1)*100:sum;changed=true;});}
    calculated.forEach(column=>{const cell=row.querySelector(`[data-column-key="${CSS.escape(column.column_key)}"] .cell-value`);if(cell)cell.textContent=values[column.column_key]==null?'':Number(values[column.column_key].toFixed(2));});return values;
  }
  document.querySelectorAll('.dynamic-report-table tbody tr').forEach(row=>row.querySelectorAll('input').forEach(input=>input.addEventListener('input',()=>{if(input.type==='number'){const valid=input.value===''||(Number(input.value)>=Number(input.min)&&Number(input.value)<=Number(input.max));input.classList.toggle('invalid',!valid);}calculateRow(row);setIndicator('تغييرات غير محفوظة','');})));
  function setIndicator(text, state){
    const el=document.querySelector('#save-indicator');
    el.textContent=text;
    el.className=state;
  }
  document.querySelector('#save-values').onclick=async()=>{const rows=[...document.querySelectorAll('.dynamic-report-table tbody tr')].map(row=>({report_student_id:Number(row.dataset.studentId),values:Object.fromEntries([...row.querySelectorAll('[data-column-key]')].map(cell=>[cell.dataset.columnKey,cell.querySelector('input')?.value??null]))}));const button=document.querySelector('#save-values');button.disabled=true;button.classList.add('is-loading');setIndicator('جارٍ الحفظ…','');try{const response=await fetch(`${APP.baseUrl}/api/reports/values.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':APP.csrf},body:JSON.stringify({report_id:REPORT_DATA.id,rows})});const data=await response.json();if(!data.ok)throw new Error(data.message);setIndicator('تم الحفظ','saved');UI.toast('حُفظت العلامات.','success',2500);document.querySelectorAll('.dynamic-report-table tbody tr').forEach(calculateRow);}catch(error){setIndicator('تعذّر الحفظ','error-text');UI.toast(error.message||'تعذّر حفظ العلامات.','error');}finally{button.disabled=false;button.classList.remove('is-loading');}};
})();