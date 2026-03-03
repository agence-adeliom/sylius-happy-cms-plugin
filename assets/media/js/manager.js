import Vue from 'vue'
/*                Libs                */
import VueClipboard from 'vue-clipboard2'
import Vue2Filters from 'vue2-filters'
import VueTippy from 'vue-tippy'

import '../sass/manager.scss';

Vue.use(Vue2Filters)
Vue.use(VueClipboard)
Vue.use(require('vue-ls'))
if(!window.EventHub){
    window.EventHub = require('vuemit')
}
if(!window.keycode){
    window.keycode  = require('keycode')
}
//window.Fuse     = require('fuse.js')

// vue-tippy
Vue.use(VueTippy, {

    popperOptions: {
        modifiers: {
            zIndex: 20000000,
            hide: {enabled: false}
        }
    }
})

// v-touch
let VueTouch = require('vue-touch')
VueTouch.registerCustomEvent('dbltap', {type: 'tap', taps: 2})
VueTouch.registerCustomEvent('hold', {type: 'press', time: 500})
Vue.use(VueTouch)

// axios
if(!window.axios){
    window.axios                  = require('axios').default
    axios.defaults.headers.common = {
        'X-Requested-With' : 'XMLHttpRequest'
    }

    // Add CSRF token interceptor for POST requests
    axios.interceptors.request.use((config) => {
        // Only add CSRF token for POST requests
        if (config.method === 'post') {
            // Get CSRF token from meta tag or data attribute
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            if (!csrfToken) {
                csrfToken = document.querySelector('[data-csrf-token]')?.getAttribute('data-csrf-token')
            }
            if (!csrfToken) {
                csrfToken = document.getElementById('media-manager')?.getAttribute('data-csrf-token')
            }

            if (csrfToken) {
                // Initialize data if not set
                if (!config.data) {
                    config.data = {}
                }

                // Add token to request body based on type
                if (config.data instanceof FormData) {
                    config.data.append('_csrf_token', csrfToken)
                } else if (typeof config.data === 'string') {
                    // If data is JSON string, parse, add token, re-stringify
                    try {
                        let dataObj = JSON.parse(config.data)
                        dataObj._csrf_token = csrfToken
                        config.data = JSON.stringify(dataObj)
                    } catch (e) {
                        // Not JSON, add as query param or header only
                    }
                } else if (typeof config.data === 'object') {
                    // For plain objects
                    config.data._csrf_token = csrfToken
                }

                // Always add as header as fallback
                if (!config.headers) {
                    config.headers = {}
                }
                config.headers['X-CSRF-Token'] = csrfToken
            } else {
                console.warn('CSRF token not found in page metadata')
            }
        }
        return config
    }, (error) => {
        return Promise.reject(error)
    })

    axios.interceptors.response.use(
        (response) => response,
        (error) => Promise.reject(error.response)
    )
}


// Echo
// import EchoLib from 'laravel-echo'
// window.Echo = new EchoLib({
//     broadcaster: 'pusher',
//     key: 'your-pusher-key'
// });

// vue-awesome
require('./modules/icons')
Vue.component('Icon', require('vue-awesome/components/Icon').default)
Vue.component('IconTypes', require('./components/utils/icon-types.vue').default)

/*                Components                */
Vue.component('MediaManager', require('./components/manager.vue').default)
Vue.component('MyNotification', require('vue-notif').default)
Vue.component('MyDropdown', require('./components/utils/dropdown.vue').default)

/*                Events                */
if ('connection' in navigator) {
    if (!navigator.connection.saveData) {
        require('./modules/events')
    }
}
