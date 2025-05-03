const container = document.querySelector('.container');
const slot = document.querySelector('.rows .slot:not(.sold)');
const count = document.getElementById('count');
const total = document.getElementById('total');
const slotselect = document.getElementById('Days');

let slotprice = +slotselect.value;

function updateselectedcount(){
    const selectedslot = document.querySelectorAll('.rows .slot.selected');

    const selectedslotcount = selectedslot.length;

    count.innerText = selectedslotcount;
    total.innerText = selectedslotcount * slotprice;
}

slotselect.addEventListener('change', e => {
    slotprice = +e.target.value;
    updateselectedcount();
});


container.addEventListener('click', (e) => {
    if(e.target.classList.contains('slot') && !e.target.classList.contains('sold')){
        e.target.classList.toggle('selected');
    }
    updateselectedcount();
});
