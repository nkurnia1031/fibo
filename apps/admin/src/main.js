import { createApp } from 'vue'
import './style.css'
import App from './App.vue'

createApp(App).mount('#app')

import('latex.js').then(({ LaTeXJSComponent }) => {
  if (!customElements.get('latex-js')) customElements.define('latex-js', LaTeXJSComponent)
})
