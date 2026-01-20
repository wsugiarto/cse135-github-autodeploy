const toggle = document.getElementById('theme-toggle');
const stored = localStorage.getItem('theme') || 'light';


document.documentElement.setAttribute('data-theme', stored);
if(stored === "light"){
  toggle.textContent = "Dark Mode";
}
else{
  toggle.textContent = "Light Mode";
}

toggle.addEventListener('click', () => {
  const current = document.documentElement.getAttribute('data-theme');
  if(current === "light"){
    next = "dark";
  }
  else{
    next = "light";
  }
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  if (next === "light"){
    toggle.textContent = "Dark Mode";

  }
  else{
    toggle.textContent = "Light Mode";
  }
});