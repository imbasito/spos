const year = new Date();
const copyright = document.querySelector('#copyrightyear');

if (copyright) {
    copyright.innerHTML = year.getFullYear();
}