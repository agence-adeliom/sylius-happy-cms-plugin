import Vue from 'vue';
import notie from 'notie';

window.Vue = Vue;
require('./manager');
Vue.component('HappyCmsMediaModal', require('./components/happy-cms-media-modal').default)
Vue.component('HappyCmsMediaDisplay', require('./components/happy-cms-media-display').default)

function dynamicallyLoadScript(url) {
    var script = document.createElement("script");
    script.src = url;
    document.head.appendChild(script);
}

dynamicallyLoadScript("https://cdnjs.cloudflare.com/ajax/libs/camanjs/4.1.2/caman.full.min.js");

if (!String.prototype.includes) {
    String.prototype.includes = function(search, start) {
        'use strict';
        if (typeof start !== 'number') {
            start = 0;
        }

        if (start + search.length > this.length) {
            return false;
        } else {
            return this.indexOf(search, start) !== -1;
        }
    };
}

window.loadMediaManager = function(event, widgetId = null) {
    window.EventHub.listen("showNotif", (obj) => {
        const types = {
            "danger": "error",
            "info": "info",
            "success": "success",
            "warning": "warning",
            "link": "neutral",
        }
        let type = types[obj.type] ? types[obj.type] : types.link;
        let duration = obj.duration ? obj.duration : 5;
        notie.alert({type: type, text: '<small>' + obj.body + '</small>', time: duration})
    })

    document.querySelectorAll("#media-holder").forEach((elm) => {
        elm.style.display = 'block';
        new Vue({ el: elm });
    })

    if(widgetId){
        document.querySelectorAll(".happy-cms-media-widget[data-widget='"+widgetId+"']").forEach((elm) => {
            new Vue({ el: elm })
        })
    }
}

if (document.readyState == 'loading') {
  // still loading, wait for the event
  window.addEventListener("DOMContentLoaded", () => {
      loadMediaManager();
  });
} else {
  // DOM is ready!
  loadMediaManager();
}

// When render in the page builder
window.addEventListener('on-load-builder', (event) => {
  console.log('Media Field: Reinitializing after AJAX load');
  var vueElements = document.querySelectorAll(".happy-cms-media-widget");
  vueElements.forEach(vueElement => {
    if(vueElement && vueElement.__vue__ && window.Vue){
      vueElement.$forceUpdate();
    } else if(vueElement && !vueElement.__vue__ && window.Vue) {
      new window.Vue({ el: vueElement });
    }
  })
});
