document.addEventListener("DOMContentLoaded", () => {

document.querySelectorAll(".sortable").forEach(table => {

const headers = table.querySelectorAll("th");
let sortStack = [];

headers.forEach(header => {
header.addEventListener("click", e => {

const body = table.querySelector("tbody");
const index = [...header.parentNode.children].indexOf(header);
const rows = Array.from(body.querySelectorAll("tr"));

if (!e.shiftKey) sortStack = [];
sortStack = sortStack.filter(s => s.index !== index);

let state = header.dataset.state || "none";
state = state === "none" ? "asc" : state === "asc" ? "desc" : "none";

if (state !== "none") sortStack.push({ index, state });
header.dataset.state = state;

headers.forEach(h => {
const s = sortStack.find(x => x.index === [...headers].indexOf(h));
h.textContent = h.textContent.replace(/[▲▼]/g,"") + (s ? (s.state==="asc"?" ▲":" ▼"):"");
h.style.color = s ? "gold" : "";
});

rows.sort((a,b)=>{
for(const s of sortStack){
const asc = s.state==="asc";
const aText = a.cells[s.index]?.innerText.trim().toLowerCase() || "";
const bText = b.cells[s.index]?.innerText.trim().toLowerCase() || "";
const cmp = aText.localeCompare(bText,undefined,{numeric:true});
if(cmp!==0) return asc ? cmp : -cmp;
}
return 0;
});

rows.forEach(row=>body.appendChild(row));

});
});

});

});

function filterTable(inputId, tableId, col=0){

const input = document.getElementById(inputId);
const filter = input.value.toUpperCase();
const rows = document.querySelectorAll(`#${tableId} tbody tr`);

rows.forEach(tr=>{
const td = tr.cells[col];
const txt = td ? td.textContent || td.innerText : "";
tr.style.display = txt.toUpperCase().includes(filter) ? "" : "none";
});

}