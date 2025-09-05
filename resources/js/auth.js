class AuthUI {
  constructor(){
    // Panels
    this.loginPanel = document.getElementById('loginPanel');
    this.registerPanel = document.getElementById('registerPanel');

    // Titles
    this.formTitle = document.getElementById('formTitle');
    this.formSubtitle = document.getElementById('formSubtitle');

    // Switch link
    this.switcherLink = document.getElementById('switcherLink');
    this.switcherText = document.getElementById('switcherText');

    // Password toggles
    this.passwordInput = document.getElementById('password');
    this.passwordToggle = document.getElementById('passwordToggle');
    this.regPassword = document.getElementById('regPassword');
    this.regPasswordToggle = document.getElementById('regPasswordToggle');

    this.init();
  }

  init(){
    // Switch between login <-> register
    this.switcherLink.addEventListener('click',(e)=>{
      e.preventDefault();
      const toRegister = !this.registerPanel.classList.contains('active');
      this.setMode(toRegister ? 'register' : 'login');
    });

    // Password toggles
    this.setupPasswordToggle(this.passwordInput, this.passwordToggle);
    this.setupPasswordToggle(this.regPassword, this.regPasswordToggle);

    // Initial mode from Blade (based on errors)
    const initialMode = document.body.dataset.initialMode || 'login';
    this.setMode(initialMode);
  }

  setMode(mode){
    if(mode==='register'){
      this.loginPanel.classList.remove('active');
      this.registerPanel.classList.add('active');
      this.formTitle.textContent = 'Create Account';
      this.formSubtitle.textContent = 'Fill your details';
      this.switcherText.textContent = 'Already have an account? ';
      this.switcherLink.textContent = 'Sign in';
    } else {
      this.registerPanel.classList.remove('active');
      this.loginPanel.classList.add('active');
      this.formTitle.textContent = 'Sign In';
      this.formSubtitle.textContent = 'Enter your credentials';
      this.switcherText.textContent = 'No account? ';
      this.switcherLink.textContent = 'Create one';
    }
  }

  setupPasswordToggle(input, btn){
    btn.addEventListener('click', ()=>{
      const type = input.type === 'password' ? 'text' : 'password';
      input.type = type;
      btn.querySelector('.toggle-text').textContent = (type === 'password' ? 'SHOW' : 'HIDE');
    });
  }
}

document.addEventListener('DOMContentLoaded',()=> new AuthUI());
