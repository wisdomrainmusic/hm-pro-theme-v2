(function(){
  'use strict';

  function qs(sel, root){ return (root||document).querySelector(sel); }

  function closest(el, sel){
    while(el && el.nodeType === 1){
      if(el.matches(sel)) return el;
      el = el.parentElement;
    }
    return null;
  }

  function ready(fn){
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  ready(function(){
    var bar = qs('#hmpro-sticky-atc');
    if(!bar) return;

    var btn = qs('#hmpro-sticky-atc-btn', bar);
    var note = qs('#hmpro-sticky-atc-note', bar);

    // Targets on Woo single product.
    var form = qs('form.cart');
    var addBtn = form ? qs('button.single_add_to_cart_button', form) : null;
    var summary = qs('.summary');
    var trigger = addBtn || qs('.single_add_to_cart_button');

    if(!form || !summary || !trigger){
      // If structure differs, keep bar hidden.
      return;
    }

    function setNote(msg){
      if(!note) return;
      note.textContent = msg || '';
    }

    // Observe when the native add-to-cart area leaves the viewport.
    var observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          bar.classList.remove('is-visible');
          bar.setAttribute('aria-hidden','true');
        } else {
          bar.classList.add('is-visible');
          bar.setAttribute('aria-hidden','false');
        }
      });
    }, { root:null, threshold:0.05 });

    observer.observe(trigger);

    function isVariableProduct(){
      return !!qs('.variations_form', form);
    }

    function variationSelected(){
      // Woo sets input[name="variation_id"] when ready.
      var v = qs('input[name="variation_id"]', form);
      if(!v) return true;
      var id = parseInt(v.value || '0', 10);
      return id > 0;
    }

    function scrollToForm(){
      try{
        form.scrollIntoView({behavior:'smooth', block:'center'});
      } catch(e){
        form.scrollIntoView(true);
      }
    }

    function clickMainATC(){
      // Prefer clicking main button so Woo validations/events run.
      if(addBtn){
        addBtn.click();
        return;
      }
      form.submit();
    }

    btn.addEventListener('click', function(){
      setNote('');

      if(isVariableProduct() && !variationSelected()){
        setNote('Lütfen seçenekleri seçin.');
        scrollToForm();
        // Flash variations table for attention.
        var vars = qs('.variations', form);
        if(vars){
          vars.style.outline = '2px solid rgba(0,0,0,.25)';
          vars.style.outlineOffset = '6px';
          setTimeout(function(){
            vars.style.outline = '';
            vars.style.outlineOffset = '';
          }, 900);
        }
        return;
      }

      clickMainATC();
    });

    // Reflect disabled state.
    var tick = function(){
      if(!btn) return;
      if(isVariableProduct() && !variationSelected()){
        btn.disabled = false; // keep clickable to guide user
      } else {
        btn.disabled = !!(addBtn && addBtn.disabled);
      }
    };

    document.addEventListener('change', function(e){
      if(closest(e.target, 'form.cart')){
        setNote('');
        setTimeout(tick, 50);
      }
    });

    // Woo triggers events when variations update; just poll lightly.
    setInterval(tick, 800);
    tick();
  });
})();
