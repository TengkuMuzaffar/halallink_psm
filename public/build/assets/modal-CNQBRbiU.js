import{V as x,W as C,O as E}from"./main-6TRAM3lj.js";const $={info:{headerClass:"bg-info text-white",icon:"bi bi-info-circle-fill",color:"#0dcaf0"},success:{headerClass:"bg-success text-white",icon:"bi bi-check-circle-fill",color:"#198754"},warning:{headerClass:"bg-warning text-dark",icon:"bi bi-exclamation-triangle-fill",color:"#ffc107"},danger:{headerClass:"bg-danger text-white",icon:"bi bi-x-circle-fill",color:"#dc3545"},loading:{headerClass:"bg-primary text-white",icon:"bi bi-arrow-repeat",color:"#0d6efd"}};let L=0,b=null;const S=()=>{if(document.getElementById("animated-modal-styles"))return;const o=`
    .modal-icon-container {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .modal-icon {
      font-size: 4rem;
      animation: iconBounce 0.6s ease-out;
    }
    
    .modal-icon.loading-icon {
      animation: iconSpin 1.5s linear infinite;
    }
    
    .modal-centered-content {
      text-align: center;
    }
    
    .modal-centered-title {
      margin-bottom: 15px;
      font-weight: 600;
    }
    
    @keyframes iconBounce {
      0% { transform: scale(0); opacity: 0; }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); opacity: 1; }
    }
    
    @keyframes iconSpin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,e=document.createElement("style");e.id="animated-modal-styles",e.textContent=o,document.head.appendChild(e)},f=o=>{if(typeof x>"u"||!E)return console.error("Bootstrap is not available. Make sure it is properly imported."),null;S();const{type:e="info",title:a="",message:r="",showClose:i=!0,buttons:c=[{label:"Close",type:"secondary",dismiss:!0}],onShown:m=null,onHidden:g=null,customModalBody:p=null,size:y=null}=o,u=`modal-${++L}`,l=$[e]||$.info,v=p?p(l,r):`
      <div class="modal-body modal-centered-content">
        <div class="modal-icon-container">
          <i class="${l.icon} modal-icon${e==="loading"?" loading-icon":""}" style="color: ${l.color};"></i>
        </div>
        <div class="modal-message">
          ${r}
        </div>
      </div>
    `,w=`
    <div class="modal fade" id="${u}" tabindex="-1" aria-labelledby="${u}-label" aria-modal="true" role="dialog">
      <div class="modal-dialog modal-dialog-centered${y?" modal-"+y:""}">
        <div class="modal-content">
          <div class="modal-header ${l.headerClass} justify-content-center">
            <h5 class="modal-title" id="${u}-label">
              ${a}
            </h5>
            ${i?'<button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal" aria-label="Close"></button>':""}
          </div>
          ${v}
          ${c.length>0?`
            <div class="modal-footer justify-content-center">
              ${c.map(t=>`
                <button type="button" class="btn btn-${t.type||"primary"}" 
                  ${t.id?`id="${t.id}"`:""} 
                  ${t.dismiss?'data-bs-dismiss="modal"':""}>
                  ${t.label}
                </button>
              `).join("")}
            </div>
          `:""}
        </div>
      </div>
    </div>
  `;document.body.insertAdjacentHTML("beforeend",w);const n=document.getElementById(u),s=new E(n);return c.forEach((t,h)=>{t.onClick&&n.querySelectorAll(".modal-footer button")[h].addEventListener("click",M=>{t.onClick(M,s)})}),m&&n.addEventListener("shown.bs.modal",m),g&&n.addEventListener("hidden.bs.modal",g),n.addEventListener("shown.bs.modal",()=>{const t=n.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');t&&t.focus()}),n.addEventListener("hide.bs.modal",()=>{if(n.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').length>0){const d=document.activeElement;n.contains(d)&&d instanceof HTMLElement&&(d.blur(),document.body.focus())}const h=n.querySelector(".modal-content");h&&(h.inert=!0)}),n.addEventListener("hidden.bs.modal",()=>{const t=n.querySelector(".modal-content");t&&(t.inert=!1)}),n.addEventListener("hidden.bs.modal",()=>{n.remove()}),s.show(),{hide:()=>s.hide(),element:n,instance:s}},T=(o,e,a={})=>f({type:"info",title:o,message:e,...a}),k=(o,e,a={})=>f({type:"success",title:o,message:e,...a}),B=(o,e,a={})=>f({type:"warning",title:o,message:e,...a}),I=(o,e,a={})=>f({type:"danger",title:o,message:e,...a}),H=(o,e,a,r=null,i={})=>f({type:i.type||"warning",title:o,message:e,buttons:[{label:i.cancelLabel||"Cancel",type:"secondary",dismiss:!0,onClick:(c,m)=>{r&&r()}},{label:i.confirmLabel||"Confirm",type:i.confirmType||"primary",onClick:(c,m)=>{a(),m.hide()}}],...i}),A=(o,e,a={})=>(b&&b.hide(),b=f({type:"loading",title:o,message:e,showClose:!1,buttons:[],...a}),b),q=()=>{b&&(b.hide(),b=null)},j=(o,e="info",a={})=>{if(typeof x>"u"||!C)return console.error("Bootstrap Toast is not available. Make sure it is properly imported."),null;const{position:r="top-right",autoHide:i=!0,delay:c=5e3,title:m="",showClose:g=!0,onShown:p=null,onHidden:y=null}=a,u=`toast-${++L}`,l=$[e]||$.info,v={"top-right":"top-0 end-0","top-left":"top-0 start-0","bottom-right":"bottom-0 end-0","bottom-left":"bottom-0 start-0","top-center":"top-0 start-50 translate-middle-x","bottom-center":"bottom-0 start-50 translate-middle-x"},w=v[r]||v["top-right"],n=`
    <div class="toast ${l.headerClass}" id="${u}" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <i class="${l.icon} me-2" style="color: ${l.color};"></i>
        <strong class="me-auto">${m||e.charAt(0).toUpperCase()+e.slice(1)}</strong>
        <small>Just now</small>
        ${g?'<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>':""}
      </div>
      <div class="toast-body">
        ${o}
      </div>
    </div>
  `;let s=document.querySelector(`.toast-container.${w}`);s||(s=document.createElement("div"),s.className=`toast-container position-fixed p-3 ${w}`,s.style.zIndex="1090",document.body.appendChild(s)),s.insertAdjacentHTML("beforeend",n);const t=document.getElementById(u),h={animation:!0,autohide:i,delay:c},d=new C(t,h);return p&&t.addEventListener("shown.bs.toast",p),y&&t.addEventListener("hidden.bs.toast",y),t.addEventListener("hidden.bs.toast",()=>{t.remove(),s.children.length===0&&s.remove()}),d.show(),{hide:()=>d.hide(),element:t,instance:d}},O={show:f,info:T,success:k,warning:B,danger:I,confirm:H,loading:A,close:q,toast:j};export{k as a,I as b,f as c,O as m,j as s};
