class AuthUI {
  constructor(){
    // Panels
    this.loginPanel = document.getElementById('loginPanel');
    this.registerPanel = document.getElementById('registerPanel');

    // Titles
    this.formTitle = document.getElementById('formTitle');
    this.formSubtitle = document.getElementById('formSubtitle');

    // Switcher
    this.switcherLink = document.getElementById('switcherLink');
    this.switcherText = document.getElementById('switcherText');

    // Toggles
    this.passwordInput = document.getElementById('password');
    this.passwordToggle = document.getElementById('passwordToggle');
    this.regPassword = document.getElementById('regPassword');
    this.regPasswordToggle = document.getElementById('regPasswordToggle');

    this.init();
  }

  init(){
    // Switch login <-> register
    if (this.switcherLink) {
      this.switcherLink.addEventListener('click',(e)=>{
        e.preventDefault();
        const toRegister = !this.registerPanel.classList.contains('active');
        this.setMode(toRegister ? 'register' : 'login');
      });
    }

    // Password toggles
    this.setupPasswordToggle(this.passwordInput, this.passwordToggle);
    this.setupPasswordToggle(this.regPassword, this.regPasswordToggle);

    // Initial mode from Blade / errors
    const initialMode = document.body.dataset.initialMode || 'login';
    this.setMode(initialMode);

    // Loader on submit (matches CSS)
    this.wireLoading(this.loginPanel, document.getElementById('loginBtn'));
    this.wireLoading(this.registerPanel, document.getElementById('registerBtn'));
  }

  setMode(mode){
    if(!this.loginPanel || !this.registerPanel) return;
    if(mode==='register'){
      this.loginPanel.classList.remove('active');
      this.registerPanel.classList.add('active');
      if (this.formTitle) this.formTitle.textContent = 'Create Account';
      if (this.formSubtitle) this.formSubtitle.textContent = 'Fill your details';
      if (this.switcherText) this.switcherText.textContent = 'Already have an account? ';
      if (this.switcherLink) this.switcherLink.textContent = 'Sign in';
    } else {
      this.registerPanel.classList.remove('active');
      this.loginPanel.classList.add('active');
      if (this.formTitle) this.formTitle.textContent = 'Sign In';
      if (this.formSubtitle) this.formSubtitle.textContent = 'Enter your credentials';
      if (this.switcherText) this.switcherText.textContent = 'No account? ';
      if (this.switcherLink) this.switcherLink.textContent = 'Create one';
    }
  }

  setupPasswordToggle(input, btn){
    if(!input || !btn) return;
    btn.addEventListener('click', ()=>{
      const type = input.type === 'password' ? 'text' : 'password';
      input.type = type;
      const t = btn.querySelector('.toggle-text');
      if (t) t.textContent = (type === 'password' ? 'SHOW' : 'HIDE');
    });
  }

  wireLoading(form, btn){
    if(!form || !btn) return;
    form.addEventListener('submit', ()=>{
      btn.classList.add('loading');
      btn.setAttribute('disabled','disabled');
    });
  }
}
document.addEventListener('DOMContentLoaded',()=> new AuthUI());
