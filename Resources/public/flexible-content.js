/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/flexible-content/flexible-content.css"
/*!******************************************************!*\
  !*** ./assets/flexible-content/flexible-content.css ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*****************************************************!*\
  !*** ./assets/flexible-content/flexible-content.js ***!
  \*****************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _flexible_content_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./flexible-content.css */ "./assets/flexible-content/flexible-content.css");
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }

var flexibleContentModule = function flexibleContentModule() {
  var self = this;
  self.blockToMove = null;
  var initIframePreviewModule = function initIframePreviewModule() {
    document.querySelectorAll('.iframe-tooltip').forEach(function (link) {
      var tooltipInstance = null;
      link.addEventListener('mouseenter', /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var url, vw, vh, width, height, tooltip;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              url = link.dataset.url;
              /*
                Sur un écran desktop 1920x1080 : 600 x 400
                Sur un écran tablet 768x1024 : 460 x 400
                Sur un mobile (360x640) : 216 x 256
               */
              vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
              vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0); // Dimensions responsives par défaut
              width = link.dataset.width || Math.min(0.6 * vw, 600); // max 600px ou 60% du viewport
              height = link.dataset.height || Math.min(0.4 * vh, 400); // max 400px ou 40% du viewport
              tooltip = new bootstrap.Tooltip(link, {
                html: true,
                template: "<div class=\"flexible-tooltip\" role=\"tooltip\"><div class=\"iframe-container\" style=\"width: ".concat(width, "px; height: ").concat(height, "px;\">\n                <div class=\"spinner-grow text-primary\" role=\"status\">\n  <span class=\"visually-hidden\">Loading...</span>\n</div>\n              </div></div>"),
                placement: 'left',
                trigger: 'manual',
                container: 'body'
              });
              tooltip.show();

              // Insertion dynamique de l’iframe après affichage du tooltip
              setTimeout(function () {
                var container = document.querySelector('.flexible-tooltip .iframe-container');
                if (container && !container.querySelector('iframe')) {
                  var iframe = document.createElement('iframe');
                  iframe.src = url;
                  iframe.style.width = width + 'px';
                  iframe.style.height = height + 'px';
                  iframe.onload = function () {
                    var loader = container.querySelector('.spinner-grow');
                    if (loader) loader.remove();
                    iframe.style.opacity = '1';
                  };
                  container.appendChild(iframe);
                }
              }, 100);
            case 1:
              return _context.a(2);
          }
        }, _callee);
      })));
      link.addEventListener('mouseleave', function () {
        var tooltip = bootstrap.Tooltip.getInstance(link);
        if (tooltip) {
          tooltip.hide();
        }
      });
    });
  };
  var initModule = function initModule() {
    // Au chargement on va initialiser le comportement lié au catalogue des blocs :

    //    - Identifier le wrapper de la colonne de gauche
    self.wFlexibleContent = document.querySelector('.w-flexible-content');

    //    - Identifier le wrapper de la colonne de droite
    self.wFlexibleBlock = document.querySelector('.w-flexible-blocks');

    //    - le dropdown des catégories (au choix) : masquer tous les blocs sauf ceux demandés
    var dropDownBlockCategories = self.wFlexibleBlock.querySelector('.block-categories');
    dropDownBlockCategories.addEventListener('change', self.listenBlockCategoriesChanges);

    //    - le dropdown des catégories (au choix) : masquer tous les blocs sauf ceux demandés
    var filterBlocks = self.wFlexibleBlock.querySelector('#block-filter');
    filterBlocks.addEventListener('keyup', self.listenBlockFilterChanges);
    document.getElementById('open-blocks').addEventListener('click', function () {
      setTimeout(function () {
        self.wFlexibleBlock.querySelector('#block-filter').focus();
      }, 300);
    });

    //    - le bouton ajouter
    self.wFlexibleBlock.querySelectorAll('a.add-flexible-block').forEach(function (addButton) {
      addButton.addEventListener('click', self.handleAddButton);
    });

    //    - Initialiser les comportements des blocs existants
    self.wFlexibleContent.querySelectorAll('.bloc-wrapper').forEach(function (block) {
      self.handleBlock(block);
    });
    self.wFlexibleContent.querySelectorAll('.move-here').forEach(function (el) {
      self.handleClickMove(el);
    });

    //    - Initialiser les positions des blocks
    self.recalculateBlockPositions();
  };
  self.handleClickMove = function (el) {
    el.addEventListener('click', function (ev) {
      if (self.blockToMove !== null) {
        // change positions
        var nextMoveLayer = self.blockToMove.nextElementSibling;
        el.after(self.blockToMove);
        self.blockToMove.after(nextMoveLayer);
        // change block position value
        self.recalculateBlockPositions();
        // disable active move behavior
        var event = new Event("click");
        self.blockToMove.querySelector('[data-action="move"]').dispatchEvent(event);
      }
    });
  };
  self.recalculateBlockPositions = function () {
    var collection = self.wFlexibleContent.closest('[data-sylius-flexible-content-field]');
    var blockPositionInputs = collection.querySelectorAll('.bloc-wrapper [data-layer="content"] input[type="hidden"]');
    var count = self.wFlexibleContent.querySelectorAll('.bloc-wrapper').length;
    blockPositionInputs.forEach(function (field) {
      if (field.id.includes("_position")) {
        field.value = count;
        count++;
      }
    });
  };
  self.handleBlock = function (block) {
    //    - click open / close
    self.handleToggleContent(block);
    //    - click move
    self.handleMoveContent(block);
    //    - click + alert trash
    self.handleRemoveContent(block);
    //    - publish
    self.handlePublishContent(block);
  };
  self.addNewBlock = function (data, index) {
    // Lorsqu'on ajoute un bloc il faut initiliser son comportement
    //    - Récupérer le prototype du wrapper du futur block
    var blockWrapper = self.getBlockWrapperPrototype();
    //    - Y injecter le prototype du block
    var newContents = self.generateNewBlock(blockWrapper, data, index);
    self.wFlexibleContent.style.opacity = 0;
    self.appendNewBlock(newContents[0]).then(function () {
      var block = document.getElementById('w-wrapper-prototype').previousElementSibling;
      document.getElementById('w-wrapper-prototype').before(newContents[1]);
      var blockName = data.blockName;
      //    - Remplacer le titre par le nom du bloc
      block.querySelector('[data-layer="title"]').innerHTML = blockName;
      //    - Init block events
      self.handleBlock(block);
      //    - Recalculer les positions
      self.recalculateBlockPositions();
      //    - executer les scripts js
      Array.from(self.wFlexibleContent.lastElementChild.querySelectorAll('script')).forEach(function (oldScript) {
        if (!oldScript.src) {
          self.evalScript(oldScript.innerHTML);
        }
      });
      /* global $ */
      //$('.ui.checkbox').checkbox();
      self.wFlexibleContent.style.opacity = 1;
    });
  };

  // action move
  self.handleMoveContent = function (block) {
    function hideMoveHere(moveButton) {
      self.wFlexibleContent.querySelectorAll('.move-here').forEach(function (el) {
        var moveEnabled = moveButton.getAttribute('clicked') === "true";
        self.blockToMove = moveEnabled ? block : null;
        el.style.display = moveEnabled ? 'block' : 'none';
        if (moveEnabled) {
          self.blockToMove.previousElementSibling.style.display = 'none';
          if (self.blockToMove.nextElementSibling) {
            self.blockToMove.nextElementSibling.style.display = 'none';
          }
        }
      });
    }
    function handleEscapeKeyPress(e) {
      if (e.key === "Escape") {
        var moveButton = block.querySelector('[data-action="move"]');
        moveButton.removeAttribute('clicked');
        moveButton.classList.remove('border-teal');
        document.removeEventListener("keydown", handleEscapeKeyPress);
        hideMoveHere(moveButton);
      }
    }
    block.querySelector('[data-action="move"]').addEventListener('click', function (event) {
      self.wFlexibleContent.querySelectorAll('[data-action="move"].border-teal').forEach(function (el) {
        if (el !== event.target) {
          el.classList.remove('border-teal');
        }
      });
      var moveButton = block.querySelector('[data-action="move"]');
      if (moveButton.getAttribute('clicked') === "true") {
        document.removeEventListener("keydown", handleEscapeKeyPress);
        moveButton.removeAttribute('clicked');
        moveButton.classList.remove('border-teal');
      } else {
        document.addEventListener("keydown", handleEscapeKeyPress);
        moveButton.setAttribute('clicked', "true");
        moveButton.classList.add('border-teal');
      }
      hideMoveHere(moveButton);
    });
  };

  // action when change published checkbox
  self.handlePublishContent = function (block) {
    // Apply checked state based on published hidden input
    var blockPublishedInputs = block.querySelectorAll('input[type="hidden"]');
    blockPublishedInputs.forEach(function (field) {
      if (field.id.includes("_block_published")) {
        block.querySelector('.block_published [type="checkbox"]').checked = field.value === '1';
        if (field.value === '1') {
          block.classList.remove('border-gray');
          block.classList.add('border-teal');
        } else {
          block.classList.add('border-gray');
          block.classList.remove('border-teal');
        }
      }
    });
    block.querySelector('.block_published [type="checkbox"]').addEventListener('change', function (event) {
      blockPublishedInputs = block.querySelectorAll('input[type="hidden"]');
      blockPublishedInputs.forEach(function (field) {
        if (field.id.includes("_block_published")) {
          field.value = event.target.checked ? '1' : '0';
        }
      });
      if (event.target.checked) {
        block.classList.remove('border-gray');
        block.classList.add('border-teal');
      } else {
        block.classList.add('border-gray');
        block.classList.remove('border-teal');
      }
    });
  };

  // action when remove a content
  self.handleRemoveContent = function (block) {
    block.querySelector('[data-action="delete"]').addEventListener('click', function () {
      if (confirm(self.wFlexibleBlock.querySelector('#confirm_sentence').textContent)) {
        var collection = block.closest('[data-sylius-flexible-content-field]');
        block.remove();
        collection.dataset.numItems = collection.querySelectorAll('.bloc-wrapper').length - 1;
      }
    });
  };

  // action when click to toggle a block
  self.handleToggleContent = function (block) {
    block.querySelector('[data-action="toggle"]').addEventListener('click', function (event) {
      var isDown = event.target.classList.contains('down');
      block.querySelector('[data-layer="content"]').style.display = isDown ? 'none' : 'block';
      event.target.classList.toggle('down');
    });
  };
  self.getBlockWrapperPrototype = function () {
    return document.getElementById('w-wrapper-prototype').querySelector('.bloc-wrapper');
  };
  self.generateNewBlock = function (blockWrapper, data, index) {
    var formTypeNamePlaceholder = data.formTypeNamePlaceholder,
      prototype = data.prototype;
    var labelRegexp = new RegExp("".concat(formTypeNamePlaceholder, "label__"), 'g');
    var nameRegexp = new RegExp(formTypeNamePlaceholder, 'g');
    var newPrototype = prototype.replace(labelRegexp, index).replace(nameRegexp, index);
    var blockElement = blockWrapper.cloneNode(true);
    var checkboxInNewBlock = blockElement.querySelector('.form-check-input');
    var labelInNewBlock = blockElement.querySelector('.form-check-label');
    var lastBlockInContent = document.getElementById('w-wrapper-prototype').parentElement.parentElement.querySelector('.w-flexible-content > .card');
    if (lastBlockInContent && checkboxInNewBlock && labelInNewBlock) {
      var idAttr = lastBlockInContent.querySelector('.form-check-input').getAttribute('id');
      // get the number part of the for attribute (block-checkbox-3 -> 3)
      var newId = parseInt(idAttr.split('-')[2], 10) + 1;
      checkboxInNewBlock.setAttribute('id', "block-checkbox-".concat(newId));
      labelInNewBlock.setAttribute('for', "block-checkbox-".concat(newId));
    }
    var moveHereWrapper = blockWrapper.nextElementSibling.cloneNode(true);
    blockElement.querySelector('[data-layer="content"]').insertAdjacentHTML('beforeend', newPrototype);
    return [blockElement, moveHereWrapper];
  };
  self.appendNewBlock = function (content) {
    var remote = [];
    document.getElementById('w-wrapper-prototype').before(content);
    Array.from(self.wFlexibleContent.lastElementChild.previousElementSibling.querySelectorAll('script')).forEach(function (oldScript) {
      if (oldScript.src) {
        remote.push(self.loadScript(oldScript.src));
      }
    });
    Array.from(self.wFlexibleContent.lastElementChild.previousElementSibling.querySelectorAll('link')).forEach(function (oldScript) {
      if (oldScript.href && oldScript.rel === 'stylesheet') {
        remote.push(self.loadStylesheet(oldScript.href));
      }
    });
    return new Promise(function (resolve) {
      Promise.all(remote).then(function () {
        setTimeout(function () {
          Array.from(self.wFlexibleContent.lastElementChild.previousElementSibling.querySelectorAll('script')).forEach(function (oldScript) {
            if (!oldScript.src) {
              var newScript = document.createElement('script');
              Array.from(oldScript.attributes).forEach(function (attr) {
                return newScript.setAttribute(attr.name, attr.value);
              });
              newScript.appendChild(document.createTextNode(oldScript.innerHTML));
              oldScript.parentNode.replaceChild(newScript, oldScript);
              self.evalScript(oldScript.innerHTML);
            }
          });
          resolve();
        }, 1);
      });
    });
  };
  self.evalScript = function (content) {
    return new Promise(function (resolve) {
      eval(content);
      resolve();
    });
  };
  self.loadScript = function (src) {
    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = src;
      script.type = 'text/javascript';
      script.onload = function () {
        return resolve(script);
      };
      script.onerror = function () {
        return reject(new Error("Style load error for ".concat(src)));
      };
      document.head.append(script);
      resolve();
    });
  };
  self.loadStylesheet = function (src) {
    return new Promise(function (resolve, reject) {
      var link = document.createElement('link');
      link.href = src;
      link.rel = 'stylesheet';
      link.onload = function () {
        return resolve(link);
      };
      link.onerror = function () {
        return reject(new Error("Style load error for ".concat(src)));
      };
      document.head.append(link);
      resolve();
    });
  };
  self.handleAddButton = function (event) {
    event.stopPropagation();
    event.preventDefault();
    var addButton = event.target;
    var collection = addButton.closest('.field-collection');
    var numItems = parseInt(collection.dataset.numItems);
    collection.dataset.numItems = ++numItems;
    self.addNewBlock(addButton.dataset, self.generateItemId());
    if (document.querySelector('.empty-flexible-content')) {
      document.querySelector('.empty-flexible-content').remove();
    }
    var myModalEl = document.querySelector('#block-list');
    var modal = bootstrap.Modal.getOrCreateInstance(myModalEl);
    modal.hide();
    return false;
  };
  self.generateItemId = function () {
    var dt = new Date().getTime();
    var uuid = 'xxxx-xxx'.replace(/[xy]/g, function (c) {
      var r = (dt + Math.random() * 16) % 16 | 0;
      dt = Math.floor(dt / 16);
      return (c == 'x' ? r : r & 0x3 | 0x8).toString(16);
    });
    return uuid;
  };
  self.listenBlockCategoriesChanges = function (event) {
    var activeCategory = event.target.value;
    event.target.closest('.w-flexible-blocks').querySelectorAll('[data-block-category]').forEach(function (card) {
      card.style.display = activeCategory === null || activeCategory === 'all_blocks' ? 'block' : 'none';
    });
    if (activeCategory) {
      event.target.closest('.w-flexible-blocks').querySelectorAll("[data-block-category=\"".concat(activeCategory, "\"]")).forEach(function (card) {
        card.style.display = 'block';
      });
    }
  };
  self.listenBlockFilterChanges = function (event) {
    var filter = event.target.value;
    event.target.closest('.w-flexible-blocks').querySelectorAll("[data-block-category] .card-title").forEach(function (title) {
      var regex = new RegExp(filter, 'i');
      title.parentElement.parentElement.style.display = regex.test(title.innerText) || !filter ? 'block' : 'none';
    });
  };
  initModule();
  initIframePreviewModule();
};
window.addEventListener('DOMContentLoaded', flexibleContentModule);
})();

/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZmxleGlibGUtY29udGVudC5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7OztBQUFBOzs7Ozs7O1VDQUE7VUFDQTs7VUFFQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTs7VUFFQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBOztVQUVBO1VBQ0E7VUFDQTs7Ozs7V0M1QkE7V0FDQTtXQUNBO1dBQ0EsdURBQXVELGlCQUFpQjtXQUN4RTtXQUNBLGdEQUFnRCxhQUFhO1dBQzdELEU7Ozs7Ozs7Ozs7OzswQkNMQSx1S0FBQUEsQ0FBQSxFQUFBQyxDQUFBLEVBQUFDLENBQUEsd0JBQUFDLE1BQUEsR0FBQUEsTUFBQSxPQUFBQyxDQUFBLEdBQUFGLENBQUEsQ0FBQUcsUUFBQSxrQkFBQUMsQ0FBQSxHQUFBSixDQUFBLENBQUFLLFdBQUEsOEJBQUFDLEVBQUFOLENBQUEsRUFBQUUsQ0FBQSxFQUFBRSxDQUFBLEVBQUFFLENBQUEsUUFBQUMsQ0FBQSxHQUFBTCxDQUFBLElBQUFBLENBQUEsQ0FBQU0sU0FBQSxZQUFBQyxTQUFBLEdBQUFQLENBQUEsR0FBQU8sU0FBQSxFQUFBQyxDQUFBLEdBQUFDLE1BQUEsQ0FBQUMsTUFBQSxDQUFBTCxDQUFBLENBQUFDLFNBQUEsVUFBQUssbUJBQUEsQ0FBQUgsQ0FBQSx1QkFBQVYsQ0FBQSxFQUFBRSxDQUFBLEVBQUFFLENBQUEsUUFBQUUsQ0FBQSxFQUFBQyxDQUFBLEVBQUFHLENBQUEsRUFBQUksQ0FBQSxNQUFBQyxDQUFBLEdBQUFYLENBQUEsUUFBQVksQ0FBQSxPQUFBQyxDQUFBLEtBQUFGLENBQUEsS0FBQWIsQ0FBQSxLQUFBZ0IsQ0FBQSxFQUFBcEIsQ0FBQSxFQUFBcUIsQ0FBQSxFQUFBQyxDQUFBLEVBQUFOLENBQUEsRUFBQU0sQ0FBQSxDQUFBQyxJQUFBLENBQUF2QixDQUFBLE1BQUFzQixDQUFBLFdBQUFBLEVBQUFyQixDQUFBLEVBQUFDLENBQUEsV0FBQU0sQ0FBQSxHQUFBUCxDQUFBLEVBQUFRLENBQUEsTUFBQUcsQ0FBQSxHQUFBWixDQUFBLEVBQUFtQixDQUFBLENBQUFmLENBQUEsR0FBQUYsQ0FBQSxFQUFBbUIsQ0FBQSxnQkFBQUMsRUFBQXBCLENBQUEsRUFBQUUsQ0FBQSxTQUFBSyxDQUFBLEdBQUFQLENBQUEsRUFBQVUsQ0FBQSxHQUFBUixDQUFBLEVBQUFILENBQUEsT0FBQWlCLENBQUEsSUFBQUYsQ0FBQSxLQUFBVixDQUFBLElBQUFMLENBQUEsR0FBQWdCLENBQUEsQ0FBQU8sTUFBQSxFQUFBdkIsQ0FBQSxVQUFBSyxDQUFBLEVBQUFFLENBQUEsR0FBQVMsQ0FBQSxDQUFBaEIsQ0FBQSxHQUFBcUIsQ0FBQSxHQUFBSCxDQUFBLENBQUFGLENBQUEsRUFBQVEsQ0FBQSxHQUFBakIsQ0FBQSxLQUFBTixDQUFBLFFBQUFJLENBQUEsR0FBQW1CLENBQUEsS0FBQXJCLENBQUEsTUFBQVEsQ0FBQSxHQUFBSixDQUFBLEVBQUFDLENBQUEsR0FBQUQsQ0FBQSxZQUFBQyxDQUFBLFdBQUFELENBQUEsTUFBQUEsQ0FBQSxNQUFBUixDQUFBLElBQUFRLENBQUEsT0FBQWMsQ0FBQSxNQUFBaEIsQ0FBQSxHQUFBSixDQUFBLFFBQUFvQixDQUFBLEdBQUFkLENBQUEsUUFBQUMsQ0FBQSxNQUFBVSxDQUFBLENBQUFDLENBQUEsR0FBQWhCLENBQUEsRUFBQWUsQ0FBQSxDQUFBZixDQUFBLEdBQUFJLENBQUEsT0FBQWMsQ0FBQSxHQUFBRyxDQUFBLEtBQUFuQixDQUFBLEdBQUFKLENBQUEsUUFBQU0sQ0FBQSxNQUFBSixDQUFBLElBQUFBLENBQUEsR0FBQXFCLENBQUEsTUFBQWpCLENBQUEsTUFBQU4sQ0FBQSxFQUFBTSxDQUFBLE1BQUFKLENBQUEsRUFBQWUsQ0FBQSxDQUFBZixDQUFBLEdBQUFxQixDQUFBLEVBQUFoQixDQUFBLGNBQUFILENBQUEsSUFBQUosQ0FBQSxhQUFBbUIsQ0FBQSxRQUFBSCxDQUFBLE9BQUFkLENBQUEscUJBQUFFLENBQUEsRUFBQVcsQ0FBQSxFQUFBUSxDQUFBLFFBQUFULENBQUEsWUFBQVUsU0FBQSx1Q0FBQVIsQ0FBQSxVQUFBRCxDQUFBLElBQUFLLENBQUEsQ0FBQUwsQ0FBQSxFQUFBUSxDQUFBLEdBQUFoQixDQUFBLEdBQUFRLENBQUEsRUFBQUwsQ0FBQSxHQUFBYSxDQUFBLEdBQUF4QixDQUFBLEdBQUFRLENBQUEsT0FBQVQsQ0FBQSxHQUFBWSxDQUFBLE1BQUFNLENBQUEsS0FBQVYsQ0FBQSxLQUFBQyxDQUFBLEdBQUFBLENBQUEsUUFBQUEsQ0FBQSxTQUFBVSxDQUFBLENBQUFmLENBQUEsUUFBQWtCLENBQUEsQ0FBQWIsQ0FBQSxFQUFBRyxDQUFBLEtBQUFPLENBQUEsQ0FBQWYsQ0FBQSxHQUFBUSxDQUFBLEdBQUFPLENBQUEsQ0FBQUMsQ0FBQSxHQUFBUixDQUFBLGFBQUFJLENBQUEsTUFBQVIsQ0FBQSxRQUFBQyxDQUFBLEtBQUFILENBQUEsWUFBQUwsQ0FBQSxHQUFBTyxDQUFBLENBQUFGLENBQUEsV0FBQUwsQ0FBQSxHQUFBQSxDQUFBLENBQUEwQixJQUFBLENBQUFuQixDQUFBLEVBQUFJLENBQUEsVUFBQWMsU0FBQSwyQ0FBQXpCLENBQUEsQ0FBQTJCLElBQUEsU0FBQTNCLENBQUEsRUFBQVcsQ0FBQSxHQUFBWCxDQUFBLENBQUE0QixLQUFBLEVBQUFwQixDQUFBLFNBQUFBLENBQUEsb0JBQUFBLENBQUEsS0FBQVIsQ0FBQSxHQUFBTyxDQUFBLGVBQUFQLENBQUEsQ0FBQTBCLElBQUEsQ0FBQW5CLENBQUEsR0FBQUMsQ0FBQSxTQUFBRyxDQUFBLEdBQUFjLFNBQUEsdUNBQUFwQixDQUFBLGdCQUFBRyxDQUFBLE9BQUFELENBQUEsR0FBQVIsQ0FBQSxjQUFBQyxDQUFBLElBQUFpQixDQUFBLEdBQUFDLENBQUEsQ0FBQWYsQ0FBQSxRQUFBUSxDQUFBLEdBQUFWLENBQUEsQ0FBQXlCLElBQUEsQ0FBQXZCLENBQUEsRUFBQWUsQ0FBQSxPQUFBRSxDQUFBLGtCQUFBcEIsQ0FBQSxJQUFBTyxDQUFBLEdBQUFSLENBQUEsRUFBQVMsQ0FBQSxNQUFBRyxDQUFBLEdBQUFYLENBQUEsY0FBQWUsQ0FBQSxtQkFBQWEsS0FBQSxFQUFBNUIsQ0FBQSxFQUFBMkIsSUFBQSxFQUFBVixDQUFBLFNBQUFoQixDQUFBLEVBQUFJLENBQUEsRUFBQUUsQ0FBQSxRQUFBSSxDQUFBLFFBQUFTLENBQUEsZ0JBQUFWLFVBQUEsY0FBQW1CLGtCQUFBLGNBQUFDLDJCQUFBLEtBQUE5QixDQUFBLEdBQUFZLE1BQUEsQ0FBQW1CLGNBQUEsTUFBQXZCLENBQUEsTUFBQUwsQ0FBQSxJQUFBSCxDQUFBLENBQUFBLENBQUEsSUFBQUcsQ0FBQSxTQUFBVyxtQkFBQSxDQUFBZCxDQUFBLE9BQUFHLENBQUEsaUNBQUFILENBQUEsR0FBQVcsQ0FBQSxHQUFBbUIsMEJBQUEsQ0FBQXJCLFNBQUEsR0FBQUMsU0FBQSxDQUFBRCxTQUFBLEdBQUFHLE1BQUEsQ0FBQUMsTUFBQSxDQUFBTCxDQUFBLFlBQUFPLEVBQUFoQixDQUFBLFdBQUFhLE1BQUEsQ0FBQW9CLGNBQUEsR0FBQXBCLE1BQUEsQ0FBQW9CLGNBQUEsQ0FBQWpDLENBQUEsRUFBQStCLDBCQUFBLEtBQUEvQixDQUFBLENBQUFrQyxTQUFBLEdBQUFILDBCQUFBLEVBQUFoQixtQkFBQSxDQUFBZixDQUFBLEVBQUFNLENBQUEseUJBQUFOLENBQUEsQ0FBQVUsU0FBQSxHQUFBRyxNQUFBLENBQUFDLE1BQUEsQ0FBQUYsQ0FBQSxHQUFBWixDQUFBLFdBQUE4QixpQkFBQSxDQUFBcEIsU0FBQSxHQUFBcUIsMEJBQUEsRUFBQWhCLG1CQUFBLENBQUFILENBQUEsaUJBQUFtQiwwQkFBQSxHQUFBaEIsbUJBQUEsQ0FBQWdCLDBCQUFBLGlCQUFBRCxpQkFBQSxHQUFBQSxpQkFBQSxDQUFBSyxXQUFBLHdCQUFBcEIsbUJBQUEsQ0FBQWdCLDBCQUFBLEVBQUF6QixDQUFBLHdCQUFBUyxtQkFBQSxDQUFBSCxDQUFBLEdBQUFHLG1CQUFBLENBQUFILENBQUEsRUFBQU4sQ0FBQSxnQkFBQVMsbUJBQUEsQ0FBQUgsQ0FBQSxFQUFBUixDQUFBLGlDQUFBVyxtQkFBQSxDQUFBSCxDQUFBLDhEQUFBd0IsWUFBQSxZQUFBQSxhQUFBLGFBQUFDLENBQUEsRUFBQTdCLENBQUEsRUFBQThCLENBQUEsRUFBQXRCLENBQUE7QUFBQSxTQUFBRCxvQkFBQWYsQ0FBQSxFQUFBRSxDQUFBLEVBQUFFLENBQUEsRUFBQUgsQ0FBQSxRQUFBTyxDQUFBLEdBQUFLLE1BQUEsQ0FBQTBCLGNBQUEsUUFBQS9CLENBQUEsdUJBQUFSLENBQUEsSUFBQVEsQ0FBQSxRQUFBTyxtQkFBQSxZQUFBeUIsbUJBQUF4QyxDQUFBLEVBQUFFLENBQUEsRUFBQUUsQ0FBQSxFQUFBSCxDQUFBLGFBQUFLLEVBQUFKLENBQUEsRUFBQUUsQ0FBQSxJQUFBVyxtQkFBQSxDQUFBZixDQUFBLEVBQUFFLENBQUEsWUFBQUYsQ0FBQSxnQkFBQXlDLE9BQUEsQ0FBQXZDLENBQUEsRUFBQUUsQ0FBQSxFQUFBSixDQUFBLFNBQUFFLENBQUEsR0FBQU0sQ0FBQSxHQUFBQSxDQUFBLENBQUFSLENBQUEsRUFBQUUsQ0FBQSxJQUFBMkIsS0FBQSxFQUFBekIsQ0FBQSxFQUFBc0MsVUFBQSxHQUFBekMsQ0FBQSxFQUFBMEMsWUFBQSxHQUFBMUMsQ0FBQSxFQUFBMkMsUUFBQSxHQUFBM0MsQ0FBQSxNQUFBRCxDQUFBLENBQUFFLENBQUEsSUFBQUUsQ0FBQSxJQUFBRSxDQUFBLGFBQUFBLENBQUEsY0FBQUEsQ0FBQSxtQkFBQVMsbUJBQUEsQ0FBQWYsQ0FBQSxFQUFBRSxDQUFBLEVBQUFFLENBQUEsRUFBQUgsQ0FBQTtBQUFBLFNBQUE0QyxtQkFBQXpDLENBQUEsRUFBQUgsQ0FBQSxFQUFBRCxDQUFBLEVBQUFFLENBQUEsRUFBQUksQ0FBQSxFQUFBZSxDQUFBLEVBQUFaLENBQUEsY0FBQUQsQ0FBQSxHQUFBSixDQUFBLENBQUFpQixDQUFBLEVBQUFaLENBQUEsR0FBQUcsQ0FBQSxHQUFBSixDQUFBLENBQUFxQixLQUFBLFdBQUF6QixDQUFBLGdCQUFBSixDQUFBLENBQUFJLENBQUEsS0FBQUksQ0FBQSxDQUFBb0IsSUFBQSxHQUFBM0IsQ0FBQSxDQUFBVyxDQUFBLElBQUFrQyxPQUFBLENBQUFDLE9BQUEsQ0FBQW5DLENBQUEsRUFBQW9DLElBQUEsQ0FBQTlDLENBQUEsRUFBQUksQ0FBQTtBQUFBLFNBQUEyQyxrQkFBQTdDLENBQUEsNkJBQUFILENBQUEsU0FBQUQsQ0FBQSxHQUFBa0QsU0FBQSxhQUFBSixPQUFBLFdBQUE1QyxDQUFBLEVBQUFJLENBQUEsUUFBQWUsQ0FBQSxHQUFBakIsQ0FBQSxDQUFBK0MsS0FBQSxDQUFBbEQsQ0FBQSxFQUFBRCxDQUFBLFlBQUFvRCxNQUFBaEQsQ0FBQSxJQUFBeUMsa0JBQUEsQ0FBQXhCLENBQUEsRUFBQW5CLENBQUEsRUFBQUksQ0FBQSxFQUFBOEMsS0FBQSxFQUFBQyxNQUFBLFVBQUFqRCxDQUFBLGNBQUFpRCxPQUFBakQsQ0FBQSxJQUFBeUMsa0JBQUEsQ0FBQXhCLENBQUEsRUFBQW5CLENBQUEsRUFBQUksQ0FBQSxFQUFBOEMsS0FBQSxFQUFBQyxNQUFBLFdBQUFqRCxDQUFBLEtBQUFnRCxLQUFBO0FBRGdDO0FBRWhDLElBQU1FLHFCQUFxQixHQUFHLFNBQXhCQSxxQkFBcUJBLENBQUEsRUFBZTtFQUN4QyxJQUFNQyxJQUFJLEdBQUcsSUFBSTtFQUVqQkEsSUFBSSxDQUFDQyxXQUFXLEdBQUcsSUFBSTtFQUV2QixJQUFNQyx1QkFBdUIsR0FBRyxTQUExQkEsdUJBQXVCQSxDQUFBLEVBQWU7SUFDeENDLFFBQVEsQ0FBQ0MsZ0JBQWdCLENBQUMsaUJBQWlCLENBQUMsQ0FBQ0MsT0FBTyxDQUFDLFVBQUFDLElBQUksRUFBSTtNQUN6RCxJQUFJQyxlQUFlLEdBQUcsSUFBSTtNQUUxQkQsSUFBSSxDQUFDRSxnQkFBZ0IsQ0FBQyxZQUFZLGVBQUFkLGlCQUFBLGNBQUFiLFlBQUEsR0FBQUUsQ0FBQSxDQUFFLFNBQUEwQixRQUFBO1FBQUEsSUFBQUMsR0FBQSxFQUFBQyxFQUFBLEVBQUFDLEVBQUEsRUFBQUMsS0FBQSxFQUFBQyxNQUFBLEVBQUFDLE9BQUE7UUFBQSxPQUFBbEMsWUFBQSxHQUFBQyxDQUFBLFdBQUFrQyxRQUFBO1VBQUEsa0JBQUFBLFFBQUEsQ0FBQW5FLENBQUE7WUFBQTtjQUMxQjZELEdBQUcsR0FBR0osSUFBSSxDQUFDVyxPQUFPLENBQUNQLEdBQUc7Y0FFNUI7QUFDZDtBQUNBO0FBQ0E7QUFDQTtjQUNvQkMsRUFBRSxHQUFHTyxJQUFJLENBQUNDLEdBQUcsQ0FBQ2hCLFFBQVEsQ0FBQ2lCLGVBQWUsQ0FBQ0MsV0FBVyxJQUFJLENBQUMsRUFBRUMsTUFBTSxDQUFDQyxVQUFVLElBQUksQ0FBQyxDQUFDO2NBQ2hGWCxFQUFFLEdBQUdNLElBQUksQ0FBQ0MsR0FBRyxDQUFDaEIsUUFBUSxDQUFDaUIsZUFBZSxDQUFDSSxZQUFZLElBQUksQ0FBQyxFQUFFRixNQUFNLENBQUNHLFdBQVcsSUFBSSxDQUFDLENBQUMsRUFDdEY7Y0FDSVosS0FBSyxHQUFHUCxJQUFJLENBQUNXLE9BQU8sQ0FBQ0osS0FBSyxJQUFJSyxJQUFJLENBQUNRLEdBQUcsQ0FBQyxHQUFHLEdBQUdmLEVBQUUsRUFBRSxHQUFHLENBQUMsRUFBRTtjQUN2REcsTUFBTSxHQUFHUixJQUFJLENBQUNXLE9BQU8sQ0FBQ0gsTUFBTSxJQUFJSSxJQUFJLENBQUNRLEdBQUcsQ0FBQyxHQUFHLEdBQUdkLEVBQUUsRUFBRSxHQUFHLENBQUMsRUFBRTtjQUV6REcsT0FBTyxHQUFHLElBQUlZLFNBQVMsQ0FBQ0MsT0FBTyxDQUFDdEIsSUFBSSxFQUFFO2dCQUN4Q3VCLElBQUksRUFBRSxJQUFJO2dCQUNWQyxRQUFRLHFHQUFBQyxNQUFBLENBQThGbEIsS0FBSyxrQkFBQWtCLE1BQUEsQ0FBZWpCLE1BQU0sK0tBSXZIO2dCQUNUa0IsU0FBUyxFQUFFLE1BQU07Z0JBQ2pCQyxPQUFPLEVBQUUsUUFBUTtnQkFDakJDLFNBQVMsRUFBRTtjQUNmLENBQUMsQ0FBQztjQUVGbkIsT0FBTyxDQUFDb0IsSUFBSSxDQUFDLENBQUM7O2NBRWQ7Y0FDQUMsVUFBVSxDQUFDLFlBQU07Z0JBQ2IsSUFBTUYsU0FBUyxHQUFHL0IsUUFBUSxDQUFDa0MsYUFBYSxDQUFDLHFDQUFxQyxDQUFDO2dCQUMvRSxJQUFJSCxTQUFTLElBQUksQ0FBQ0EsU0FBUyxDQUFDRyxhQUFhLENBQUMsUUFBUSxDQUFDLEVBQUU7a0JBQ2pELElBQU1DLE1BQU0sR0FBR25DLFFBQVEsQ0FBQ29DLGFBQWEsQ0FBQyxRQUFRLENBQUM7a0JBQy9DRCxNQUFNLENBQUNFLEdBQUcsR0FBRzlCLEdBQUc7a0JBQ2hCNEIsTUFBTSxDQUFDRyxLQUFLLENBQUM1QixLQUFLLEdBQUdBLEtBQUssR0FBQyxJQUFJO2tCQUMvQnlCLE1BQU0sQ0FBQ0csS0FBSyxDQUFDM0IsTUFBTSxHQUFHQSxNQUFNLEdBQUMsSUFBSTtrQkFFakN3QixNQUFNLENBQUNJLE1BQU0sR0FBRyxZQUFNO29CQUNsQixJQUFNQyxNQUFNLEdBQUdULFNBQVMsQ0FBQ0csYUFBYSxDQUFDLGVBQWUsQ0FBQztvQkFDdkQsSUFBSU0sTUFBTSxFQUFFQSxNQUFNLENBQUNDLE1BQU0sQ0FBQyxDQUFDO29CQUMzQk4sTUFBTSxDQUFDRyxLQUFLLENBQUNJLE9BQU8sR0FBRyxHQUFHO2tCQUM5QixDQUFDO2tCQUVEWCxTQUFTLENBQUNZLFdBQVcsQ0FBQ1IsTUFBTSxDQUFDO2dCQUNqQztjQUNKLENBQUMsRUFBRSxHQUFHLENBQUM7WUFBQztjQUFBLE9BQUF0QixRQUFBLENBQUFsRCxDQUFBO1VBQUE7UUFBQSxHQUFBMkMsT0FBQTtNQUFBLENBQ1gsR0FBQztNQUVGSCxJQUFJLENBQUNFLGdCQUFnQixDQUFDLFlBQVksRUFBRSxZQUFNO1FBQ3RDLElBQU1PLE9BQU8sR0FBR1ksU0FBUyxDQUFDQyxPQUFPLENBQUNtQixXQUFXLENBQUN6QyxJQUFJLENBQUM7UUFDbkQsSUFBSVMsT0FBTyxFQUFFO1VBQ1RBLE9BQU8sQ0FBQ2lDLElBQUksQ0FBQyxDQUFDO1FBQ2xCO01BQ0osQ0FBQyxDQUFDO0lBQ04sQ0FBQyxDQUFDO0VBQ04sQ0FBQztFQUVELElBQU1DLFVBQVUsR0FBRyxTQUFiQSxVQUFVQSxDQUFBLEVBQWU7SUFDN0I7O0lBRUE7SUFDQWpELElBQUksQ0FBQ2tELGdCQUFnQixHQUFHL0MsUUFBUSxDQUFDa0MsYUFBYSxDQUFDLHFCQUFxQixDQUFDOztJQUVyRTtJQUNBckMsSUFBSSxDQUFDbUQsY0FBYyxHQUFHaEQsUUFBUSxDQUFDa0MsYUFBYSxDQUFDLG9CQUFvQixDQUFDOztJQUVsRTtJQUNBLElBQU1lLHVCQUF1QixHQUFHcEQsSUFBSSxDQUFDbUQsY0FBYyxDQUFDZCxhQUFhLENBQUMsbUJBQW1CLENBQUM7SUFDdEZlLHVCQUF1QixDQUFDNUMsZ0JBQWdCLENBQUMsUUFBUSxFQUFFUixJQUFJLENBQUNxRCw0QkFBNEIsQ0FBQzs7SUFFckY7SUFDQSxJQUFNQyxZQUFZLEdBQUd0RCxJQUFJLENBQUNtRCxjQUFjLENBQUNkLGFBQWEsQ0FBQyxlQUFlLENBQUM7SUFDckVpQixZQUFZLENBQUM5QyxnQkFBZ0IsQ0FBQyxPQUFPLEVBQUVSLElBQUksQ0FBQ3VELHdCQUF3QixDQUFDO0lBRXZFcEQsUUFBUSxDQUFDcUQsY0FBYyxDQUFDLGFBQWEsQ0FBQyxDQUFDaEQsZ0JBQWdCLENBQUMsT0FBTyxFQUFFLFlBQU07TUFDbkU0QixVQUFVLENBQUMsWUFBTTtRQUNicEMsSUFBSSxDQUFDbUQsY0FBYyxDQUFDZCxhQUFhLENBQUMsZUFBZSxDQUFDLENBQUNvQixLQUFLLENBQUMsQ0FBQztNQUM5RCxDQUFDLEVBQUUsR0FBRyxDQUFDO0lBQ1gsQ0FBQyxDQUFDOztJQUVGO0lBQ0F6RCxJQUFJLENBQUNtRCxjQUFjLENBQUMvQyxnQkFBZ0IsQ0FBQyxzQkFBc0IsQ0FBQyxDQUN2REMsT0FBTyxDQUFDLFVBQUNxRCxTQUFTLEVBQUs7TUFDdEJBLFNBQVMsQ0FBQ2xELGdCQUFnQixDQUFDLE9BQU8sRUFBRVIsSUFBSSxDQUFDMkQsZUFBZSxDQUFDO0lBQzNELENBQUMsQ0FBQzs7SUFFTjtJQUNBM0QsSUFBSSxDQUFDa0QsZ0JBQWdCLENBQUM5QyxnQkFBZ0IsQ0FBQyxlQUFlLENBQUMsQ0FDbERDLE9BQU8sQ0FBQyxVQUFDdUQsS0FBSyxFQUFLO01BQ2xCNUQsSUFBSSxDQUFDNkQsV0FBVyxDQUFDRCxLQUFLLENBQUM7SUFDekIsQ0FBQyxDQUFDO0lBRU41RCxJQUFJLENBQUNrRCxnQkFBZ0IsQ0FBQzlDLGdCQUFnQixDQUFDLFlBQVksQ0FBQyxDQUFDQyxPQUFPLENBQUMsVUFBQXlELEVBQUUsRUFBSTtNQUNqRTlELElBQUksQ0FBQytELGVBQWUsQ0FBQ0QsRUFBRSxDQUFDO0lBQzFCLENBQUMsQ0FBQzs7SUFFRjtJQUNBOUQsSUFBSSxDQUFDZ0UseUJBQXlCLENBQUMsQ0FBQztFQUNsQyxDQUFDO0VBRURoRSxJQUFJLENBQUMrRCxlQUFlLEdBQUcsVUFBU0QsRUFBRSxFQUFFO0lBQ2xDQSxFQUFFLENBQUN0RCxnQkFBZ0IsQ0FBQyxPQUFPLEVBQUUsVUFBU3lELEVBQUUsRUFBRTtNQUN4QyxJQUFJakUsSUFBSSxDQUFDQyxXQUFXLEtBQUssSUFBSSxFQUFFO1FBQzdCO1FBQ0EsSUFBTWlFLGFBQWEsR0FBR2xFLElBQUksQ0FBQ0MsV0FBVyxDQUFDa0Usa0JBQWtCO1FBQ3pETCxFQUFFLENBQUNNLEtBQUssQ0FBQ3BFLElBQUksQ0FBQ0MsV0FBVyxDQUFDO1FBQzFCRCxJQUFJLENBQUNDLFdBQVcsQ0FBQ21FLEtBQUssQ0FBQ0YsYUFBYSxDQUFDO1FBQ3JDO1FBQ0FsRSxJQUFJLENBQUNnRSx5QkFBeUIsQ0FBQyxDQUFDO1FBQ2hDO1FBQ0EsSUFBTUssS0FBSyxHQUFHLElBQUlDLEtBQUssQ0FBQyxPQUFPLENBQUM7UUFDaEN0RSxJQUFJLENBQUNDLFdBQVcsQ0FDWG9DLGFBQWEsQ0FBQyxzQkFBc0IsQ0FBQyxDQUNyQ2tDLGFBQWEsQ0FBQ0YsS0FBSyxDQUFDO01BQzNCO0lBQ0YsQ0FBQyxDQUFDO0VBQ0osQ0FBQztFQUVEckUsSUFBSSxDQUFDZ0UseUJBQXlCLEdBQUcsWUFBVztJQUMxQyxJQUFNUSxVQUFVLEdBQUd4RSxJQUFJLENBQUNrRCxnQkFBZ0IsQ0FBQ3VCLE9BQU8sQ0FBQyxzQ0FBc0MsQ0FBQztJQUN4RixJQUFNQyxtQkFBbUIsR0FBR0YsVUFBVSxDQUNqQ3BFLGdCQUFnQixDQUFDLDJEQUEyRCxDQUFDO0lBQ2xGLElBQUl1RSxLQUFLLEdBQUczRSxJQUFJLENBQUNrRCxnQkFBZ0IsQ0FBQzlDLGdCQUFnQixDQUFDLGVBQWUsQ0FBQyxDQUFDbkMsTUFBTTtJQUMxRXlHLG1CQUFtQixDQUFDckUsT0FBTyxDQUFDLFVBQUN1RSxLQUFLLEVBQUs7TUFDckMsSUFBSUEsS0FBSyxDQUFDQyxFQUFFLENBQUNDLFFBQVEsQ0FBQyxXQUFXLENBQUMsRUFBRTtRQUNsQ0YsS0FBSyxDQUFDdEcsS0FBSyxHQUFHcUcsS0FBSztRQUNuQkEsS0FBSyxFQUFHO01BQ1Y7SUFDRixDQUFDLENBQUM7RUFDSixDQUFDO0VBRUQzRSxJQUFJLENBQUM2RCxXQUFXLEdBQUcsVUFBU0QsS0FBSyxFQUFFO0lBQ2pDO0lBQ0E1RCxJQUFJLENBQUMrRSxtQkFBbUIsQ0FBQ25CLEtBQUssQ0FBQztJQUMvQjtJQUNBNUQsSUFBSSxDQUFDZ0YsaUJBQWlCLENBQUNwQixLQUFLLENBQUM7SUFDN0I7SUFDQTVELElBQUksQ0FBQ2lGLG1CQUFtQixDQUFDckIsS0FBSyxDQUFDO0lBQy9CO0lBQ0E1RCxJQUFJLENBQUNrRixvQkFBb0IsQ0FBQ3RCLEtBQUssQ0FBQztFQUNsQyxDQUFDO0VBRUQ1RCxJQUFJLENBQUNtRixXQUFXLEdBQUcsVUFBVUMsSUFBSSxFQUFFQyxLQUFLLEVBQUU7SUFDeEM7SUFDQTtJQUNBLElBQU1DLFlBQVksR0FBR3RGLElBQUksQ0FBQ3VGLHdCQUF3QixDQUFDLENBQUM7SUFDcEQ7SUFDQSxJQUFNQyxXQUFXLEdBQUd4RixJQUFJLENBQUN5RixnQkFBZ0IsQ0FBQ0gsWUFBWSxFQUFFRixJQUFJLEVBQUVDLEtBQUssQ0FBQztJQUNwRXJGLElBQUksQ0FBQ2tELGdCQUFnQixDQUFDVCxLQUFLLENBQUNJLE9BQU8sR0FBRyxDQUFDO0lBQ3ZDN0MsSUFBSSxDQUFDMEYsY0FBYyxDQUFDRixXQUFXLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FDOUIvRixJQUFJLENBQUMsWUFBTTtNQUNWLElBQU1tRSxLQUFLLEdBQUd6RCxRQUFRLENBQUNxRCxjQUFjLENBQUMscUJBQXFCLENBQUMsQ0FBQ21DLHNCQUFzQjtNQUNuRnhGLFFBQVEsQ0FBQ3FELGNBQWMsQ0FBQyxxQkFBcUIsQ0FBQyxDQUFDb0MsTUFBTSxDQUFDSixXQUFXLENBQUMsQ0FBQyxDQUFDLENBQUM7TUFDckUsSUFBUUssU0FBUyxHQUFLVCxJQUFJLENBQWxCUyxTQUFTO01BQ2pCO01BQ0FqQyxLQUFLLENBQUN2QixhQUFhLENBQUMsc0JBQXNCLENBQUMsQ0FBQ3lELFNBQVMsR0FBR0QsU0FBUztNQUNqRTtNQUNBN0YsSUFBSSxDQUFDNkQsV0FBVyxDQUFDRCxLQUFLLENBQUM7TUFDdkI7TUFDQTVELElBQUksQ0FBQ2dFLHlCQUF5QixDQUFDLENBQUM7TUFDaEM7TUFDQStCLEtBQUssQ0FBQ0MsSUFBSSxDQUFDaEcsSUFBSSxDQUFDa0QsZ0JBQWdCLENBQUMrQyxnQkFBZ0IsQ0FBQzdGLGdCQUFnQixDQUFDLFFBQVEsQ0FBQyxDQUFDLENBQ3hFQyxPQUFPLENBQUMsVUFBQzZGLFNBQVMsRUFBSztRQUN0QixJQUFJLENBQUNBLFNBQVMsQ0FBQzFELEdBQUcsRUFBRTtVQUNsQnhDLElBQUksQ0FBQ21HLFVBQVUsQ0FBQ0QsU0FBUyxDQUFDSixTQUFTLENBQUM7UUFDdEM7TUFDRixDQUFDLENBQUM7TUFDTjtNQUNBO01BQ0E5RixJQUFJLENBQUNrRCxnQkFBZ0IsQ0FBQ1QsS0FBSyxDQUFDSSxPQUFPLEdBQUcsQ0FBQztJQUN6QyxDQUFDLENBQUM7RUFDUixDQUFDOztFQUVEO0VBQ0E3QyxJQUFJLENBQUNnRixpQkFBaUIsR0FBRyxVQUFVcEIsS0FBSyxFQUFFO0lBQ3RDLFNBQVN3QyxZQUFZQSxDQUFDQyxVQUFVLEVBQUU7TUFDOUJyRyxJQUFJLENBQUNrRCxnQkFBZ0IsQ0FBQzlDLGdCQUFnQixDQUFDLFlBQVksQ0FBQyxDQUFDQyxPQUFPLENBQUMsVUFBQXlELEVBQUUsRUFBSTtRQUMvRCxJQUFNd0MsV0FBVyxHQUFHRCxVQUFVLENBQUNFLFlBQVksQ0FBQyxTQUFTLENBQUMsS0FBSyxNQUFNO1FBQ2pFdkcsSUFBSSxDQUFDQyxXQUFXLEdBQUdxRyxXQUFXLEdBQUcxQyxLQUFLLEdBQUcsSUFBSTtRQUM3Q0UsRUFBRSxDQUFDckIsS0FBSyxDQUFDK0QsT0FBTyxHQUFHRixXQUFXLEdBQUcsT0FBTyxHQUFHLE1BQU07UUFDakQsSUFBSUEsV0FBVyxFQUFFO1VBQ2J0RyxJQUFJLENBQUNDLFdBQVcsQ0FBQzBGLHNCQUFzQixDQUFDbEQsS0FBSyxDQUFDK0QsT0FBTyxHQUFHLE1BQU07VUFDOUQsSUFBSXhHLElBQUksQ0FBQ0MsV0FBVyxDQUFDa0Usa0JBQWtCLEVBQUU7WUFDckNuRSxJQUFJLENBQUNDLFdBQVcsQ0FBQ2tFLGtCQUFrQixDQUFDMUIsS0FBSyxDQUFDK0QsT0FBTyxHQUFHLE1BQU07VUFDOUQ7UUFDSjtNQUNKLENBQUMsQ0FBQztJQUNOO0lBRUEsU0FBU0Msb0JBQW9CQSxDQUFDaEssQ0FBQyxFQUFFO01BQzdCLElBQUlBLENBQUMsQ0FBQ2lLLEdBQUcsS0FBSyxRQUFRLEVBQUU7UUFDcEIsSUFBTUwsVUFBVSxHQUFHekMsS0FBSyxDQUFDdkIsYUFBYSxDQUFDLHNCQUFzQixDQUFDO1FBQzlEZ0UsVUFBVSxDQUFDTSxlQUFlLENBQUMsU0FBUyxDQUFDO1FBQ3JDTixVQUFVLENBQUNPLFNBQVMsQ0FBQ2hFLE1BQU0sQ0FBQyxhQUFhLENBQUM7UUFDMUN6QyxRQUFRLENBQUMwRyxtQkFBbUIsQ0FBQyxTQUFTLEVBQUVKLG9CQUFvQixDQUFDO1FBQzdETCxZQUFZLENBQUNDLFVBQVUsQ0FBQztNQUM1QjtJQUNOO0lBRUF6QyxLQUFLLENBQUN2QixhQUFhLENBQUMsc0JBQXNCLENBQUMsQ0FDdEM3QixnQkFBZ0IsQ0FBQyxPQUFPLEVBQUUsVUFBVTZELEtBQUssRUFBRTtNQUMxQ3JFLElBQUksQ0FBQ2tELGdCQUFnQixDQUFDOUMsZ0JBQWdCLENBQUMsa0NBQWtDLENBQUMsQ0FBQ0MsT0FBTyxDQUFDLFVBQUF5RCxFQUFFLEVBQUk7UUFBRSxJQUFJQSxFQUFFLEtBQUtPLEtBQUssQ0FBQ3lDLE1BQU0sRUFBRTtVQUFFaEQsRUFBRSxDQUFDOEMsU0FBUyxDQUFDaEUsTUFBTSxDQUFDLGFBQWEsQ0FBQztRQUFFO01BQUUsQ0FBQyxDQUFDO01BQzlKLElBQU15RCxVQUFVLEdBQUd6QyxLQUFLLENBQUN2QixhQUFhLENBQUMsc0JBQXNCLENBQUM7TUFDOUQsSUFBSWdFLFVBQVUsQ0FBQ0UsWUFBWSxDQUFDLFNBQVMsQ0FBQyxLQUFLLE1BQU0sRUFBRTtRQUMvQ3BHLFFBQVEsQ0FBQzBHLG1CQUFtQixDQUFDLFNBQVMsRUFBRUosb0JBQW9CLENBQUM7UUFDN0RKLFVBQVUsQ0FBQ00sZUFBZSxDQUFDLFNBQVMsQ0FBQztRQUNyQ04sVUFBVSxDQUFDTyxTQUFTLENBQUNoRSxNQUFNLENBQUMsYUFBYSxDQUFDO01BQzlDLENBQUMsTUFBTTtRQUNIekMsUUFBUSxDQUFDSyxnQkFBZ0IsQ0FBQyxTQUFTLEVBQUVpRyxvQkFBb0IsQ0FBQztRQUMxREosVUFBVSxDQUFDVSxZQUFZLENBQUMsU0FBUyxFQUFFLE1BQU0sQ0FBQztRQUMxQ1YsVUFBVSxDQUFDTyxTQUFTLENBQUNJLEdBQUcsQ0FBQyxhQUFhLENBQUM7TUFDM0M7TUFDQVosWUFBWSxDQUFDQyxVQUFVLENBQUM7SUFDMUIsQ0FBQyxDQUFDO0VBQ1IsQ0FBQzs7RUFFRDtFQUNBckcsSUFBSSxDQUFDa0Ysb0JBQW9CLEdBQUcsVUFBVXRCLEtBQUssRUFBRTtJQUUzQztJQUNBLElBQUlxRCxvQkFBb0IsR0FBR3JELEtBQUssQ0FDM0J4RCxnQkFBZ0IsQ0FBQyxzQkFBc0IsQ0FBQztJQUM3QzZHLG9CQUFvQixDQUFDNUcsT0FBTyxDQUFDLFVBQVV1RSxLQUFLLEVBQUU7TUFDNUMsSUFBSUEsS0FBSyxDQUFDQyxFQUFFLENBQUNDLFFBQVEsQ0FBQyxrQkFBa0IsQ0FBQyxFQUFFO1FBQ3pDbEIsS0FBSyxDQUFDdkIsYUFBYSxDQUFDLG9DQUFvQyxDQUFDLENBQUM2RSxPQUFPLEdBQUd0QyxLQUFLLENBQUN0RyxLQUFLLEtBQUssR0FBRztRQUN2RixJQUFJc0csS0FBSyxDQUFDdEcsS0FBSyxLQUFLLEdBQUcsRUFBRTtVQUN2QnNGLEtBQUssQ0FBQ2dELFNBQVMsQ0FBQ2hFLE1BQU0sQ0FBQyxhQUFhLENBQUM7VUFDckNnQixLQUFLLENBQUNnRCxTQUFTLENBQUNJLEdBQUcsQ0FBQyxhQUFhLENBQUM7UUFDcEMsQ0FBQyxNQUFNO1VBQ0xwRCxLQUFLLENBQUNnRCxTQUFTLENBQUNJLEdBQUcsQ0FBQyxhQUFhLENBQUM7VUFDbENwRCxLQUFLLENBQUNnRCxTQUFTLENBQUNoRSxNQUFNLENBQUMsYUFBYSxDQUFDO1FBQ3ZDO01BQ0Y7SUFDRixDQUFDLENBQUM7SUFFRmdCLEtBQUssQ0FBQ3ZCLGFBQWEsQ0FBQyxvQ0FBb0MsQ0FBQyxDQUNwRDdCLGdCQUFnQixDQUFDLFFBQVEsRUFBRSxVQUFVNkQsS0FBSyxFQUFFO01BQzNDNEMsb0JBQW9CLEdBQUdyRCxLQUFLLENBQ3ZCeEQsZ0JBQWdCLENBQUMsc0JBQXNCLENBQUM7TUFDN0M2RyxvQkFBb0IsQ0FBQzVHLE9BQU8sQ0FBQyxVQUFVdUUsS0FBSyxFQUFFO1FBQzVDLElBQUlBLEtBQUssQ0FBQ0MsRUFBRSxDQUFDQyxRQUFRLENBQUMsa0JBQWtCLENBQUMsRUFBRTtVQUN6Q0YsS0FBSyxDQUFDdEcsS0FBSyxHQUFHK0YsS0FBSyxDQUFDeUMsTUFBTSxDQUFDSSxPQUFPLEdBQUcsR0FBRyxHQUFHLEdBQUc7UUFDaEQ7TUFDRixDQUFDLENBQUM7TUFDRixJQUFJN0MsS0FBSyxDQUFDeUMsTUFBTSxDQUFDSSxPQUFPLEVBQUU7UUFDeEJ0RCxLQUFLLENBQUNnRCxTQUFTLENBQUNoRSxNQUFNLENBQUMsYUFBYSxDQUFDO1FBQ3JDZ0IsS0FBSyxDQUFDZ0QsU0FBUyxDQUFDSSxHQUFHLENBQUMsYUFBYSxDQUFDO01BQ3BDLENBQUMsTUFBTTtRQUNMcEQsS0FBSyxDQUFDZ0QsU0FBUyxDQUFDSSxHQUFHLENBQUMsYUFBYSxDQUFDO1FBQ2xDcEQsS0FBSyxDQUFDZ0QsU0FBUyxDQUFDaEUsTUFBTSxDQUFDLGFBQWEsQ0FBQztNQUN2QztJQUNGLENBQUMsQ0FBQztFQUNSLENBQUM7O0VBRUQ7RUFDQTVDLElBQUksQ0FBQ2lGLG1CQUFtQixHQUFHLFVBQVVyQixLQUFLLEVBQUU7SUFDMUNBLEtBQUssQ0FBQ3ZCLGFBQWEsQ0FBQyx3QkFBd0IsQ0FBQyxDQUN4QzdCLGdCQUFnQixDQUFDLE9BQU8sRUFBRSxZQUFZO01BQ3JDLElBQUkyRyxPQUFPLENBQUNuSCxJQUFJLENBQUNtRCxjQUFjLENBQUNkLGFBQWEsQ0FBQyxtQkFBbUIsQ0FBQyxDQUFDK0UsV0FBVyxDQUFDLEVBQUU7UUFDL0UsSUFBTTVDLFVBQVUsR0FBR1osS0FBSyxDQUFDYSxPQUFPLENBQUMsc0NBQXNDLENBQUM7UUFDeEViLEtBQUssQ0FBQ2hCLE1BQU0sQ0FBQyxDQUFDO1FBQ2Q0QixVQUFVLENBQUN2RCxPQUFPLENBQUNvRyxRQUFRLEdBQUc3QyxVQUFVLENBQUNwRSxnQkFBZ0IsQ0FBQyxlQUFlLENBQUMsQ0FBQ25DLE1BQU0sR0FBRyxDQUFDO01BQ3ZGO0lBQ0YsQ0FBQyxDQUFDO0VBQ1IsQ0FBQzs7RUFFRDtFQUNBK0IsSUFBSSxDQUFDK0UsbUJBQW1CLEdBQUcsVUFBVW5CLEtBQUssRUFBRTtJQUMxQ0EsS0FBSyxDQUFDdkIsYUFBYSxDQUFDLHdCQUF3QixDQUFDLENBQ3hDN0IsZ0JBQWdCLENBQUMsT0FBTyxFQUFFLFVBQVU2RCxLQUFLLEVBQUU7TUFDeEMsSUFBTWlELE1BQU0sR0FBR2pELEtBQUssQ0FBQ3lDLE1BQU0sQ0FBQ0YsU0FBUyxDQUFDVyxRQUFRLENBQUMsTUFBTSxDQUFDO01BQ3REM0QsS0FBSyxDQUFDdkIsYUFBYSxDQUFDLHdCQUF3QixDQUFDLENBQUNJLEtBQUssQ0FBQytELE9BQU8sR0FBR2MsTUFBTSxHQUFHLE1BQU0sR0FBRyxPQUFPO01BQ3ZGakQsS0FBSyxDQUFDeUMsTUFBTSxDQUFDRixTQUFTLENBQUNZLE1BQU0sQ0FBQyxNQUFNLENBQUM7SUFDekMsQ0FBQyxDQUFDO0VBQ1IsQ0FBQztFQUVEeEgsSUFBSSxDQUFDdUYsd0JBQXdCLEdBQUcsWUFBWTtJQUMxQyxPQUFPcEYsUUFBUSxDQUFDcUQsY0FBYyxDQUFDLHFCQUFxQixDQUFDLENBQ2hEbkIsYUFBYSxDQUFDLGVBQWUsQ0FBQztFQUNyQyxDQUFDO0VBRURyQyxJQUFJLENBQUN5RixnQkFBZ0IsR0FBRyxVQUFVSCxZQUFZLEVBQUVGLElBQUksRUFBRUMsS0FBSyxFQUFFO0lBQzNELElBQVFvQyx1QkFBdUIsR0FBZ0JyQyxJQUFJLENBQTNDcUMsdUJBQXVCO01BQUV0SyxTQUFTLEdBQUtpSSxJQUFJLENBQWxCakksU0FBUztJQUMxQyxJQUFNdUssV0FBVyxHQUFHLElBQUlDLE1BQU0sSUFBQTVGLE1BQUEsQ0FBSTBGLHVCQUF1QixjQUFXLEdBQUcsQ0FBQztJQUN4RSxJQUFNRyxVQUFVLEdBQUcsSUFBSUQsTUFBTSxDQUFDRix1QkFBdUIsRUFBRSxHQUFHLENBQUM7SUFFM0QsSUFBTUksWUFBWSxHQUFHMUssU0FBUyxDQUN6QjJLLE9BQU8sQ0FBQ0osV0FBVyxFQUFFckMsS0FBSyxDQUFDLENBQzNCeUMsT0FBTyxDQUFDRixVQUFVLEVBQUV2QyxLQUFLLENBQUM7SUFFL0IsSUFBTTBDLFlBQVksR0FBR3pDLFlBQVksQ0FBQzBDLFNBQVMsQ0FBQyxJQUFJLENBQUM7SUFFakQsSUFBTUMsa0JBQWtCLEdBQUdGLFlBQVksQ0FBQzFGLGFBQWEsQ0FBQyxtQkFBbUIsQ0FBQztJQUMxRSxJQUFNNkYsZUFBZSxHQUFHSCxZQUFZLENBQUMxRixhQUFhLENBQUMsbUJBQW1CLENBQUM7SUFDdkUsSUFBTThGLGtCQUFrQixHQUFHaEksUUFBUSxDQUFDcUQsY0FBYyxDQUFDLHFCQUFxQixDQUFDLENBQUM0RSxhQUFhLENBQUNBLGFBQWEsQ0FBQy9GLGFBQWEsQ0FBQyw2QkFBNkIsQ0FBQztJQUNsSixJQUFJOEYsa0JBQWtCLElBQUlGLGtCQUFrQixJQUFJQyxlQUFlLEVBQUU7TUFDN0QsSUFBTUcsTUFBTSxHQUFHRixrQkFBa0IsQ0FBQzlGLGFBQWEsQ0FBQyxtQkFBbUIsQ0FBQyxDQUFDa0UsWUFBWSxDQUFDLElBQUksQ0FBQztNQUN2RjtNQUNBLElBQU0rQixLQUFLLEdBQUdDLFFBQVEsQ0FBQ0YsTUFBTSxDQUFDRyxLQUFLLENBQUMsR0FBRyxDQUFDLENBQUMsQ0FBQyxDQUFDLEVBQUUsRUFBRSxDQUFDLEdBQUcsQ0FBQztNQUNwRFAsa0JBQWtCLENBQUNsQixZQUFZLENBQUMsSUFBSSxvQkFBQWhGLE1BQUEsQ0FBb0J1RyxLQUFLLENBQUUsQ0FBQztNQUNoRUosZUFBZSxDQUFDbkIsWUFBWSxDQUFDLEtBQUssb0JBQUFoRixNQUFBLENBQW9CdUcsS0FBSyxDQUFFLENBQUM7SUFDbEU7SUFFQSxJQUFNRyxlQUFlLEdBQUduRCxZQUFZLENBQUNuQixrQkFBa0IsQ0FBQzZELFNBQVMsQ0FBQyxJQUFJLENBQUM7SUFDdkVELFlBQVksQ0FBQzFGLGFBQWEsQ0FBQyx3QkFBd0IsQ0FBQyxDQUMvQ3FHLGtCQUFrQixDQUFDLFdBQVcsRUFBRWIsWUFBWSxDQUFDO0lBRWxELE9BQU8sQ0FBQ0UsWUFBWSxFQUFFVSxlQUFlLENBQUM7RUFDeEMsQ0FBQztFQUVEekksSUFBSSxDQUFDMEYsY0FBYyxHQUFHLFVBQVVpRCxPQUFPLEVBQUU7SUFFdkMsSUFBTUMsTUFBTSxHQUFHLEVBQUU7SUFFakJ6SSxRQUFRLENBQUNxRCxjQUFjLENBQUMscUJBQXFCLENBQUMsQ0FBQ29DLE1BQU0sQ0FBQytDLE9BQU8sQ0FBQztJQUU5RDVDLEtBQUssQ0FBQ0MsSUFBSSxDQUFDaEcsSUFBSSxDQUFDa0QsZ0JBQWdCLENBQUMrQyxnQkFBZ0IsQ0FBQ04sc0JBQXNCLENBQUN2RixnQkFBZ0IsQ0FBQyxRQUFRLENBQUMsQ0FBQyxDQUMvRkMsT0FBTyxDQUFDLFVBQUM2RixTQUFTLEVBQUs7TUFDdEIsSUFBSUEsU0FBUyxDQUFDMUQsR0FBRyxFQUFFO1FBQ2pCb0csTUFBTSxDQUFDQyxJQUFJLENBQUM3SSxJQUFJLENBQUM4SSxVQUFVLENBQUM1QyxTQUFTLENBQUMxRCxHQUFHLENBQUMsQ0FBQztNQUM3QztJQUNGLENBQUMsQ0FBQztJQUVOdUQsS0FBSyxDQUFDQyxJQUFJLENBQUNoRyxJQUFJLENBQUNrRCxnQkFBZ0IsQ0FBQytDLGdCQUFnQixDQUFDTixzQkFBc0IsQ0FBQ3ZGLGdCQUFnQixDQUFDLE1BQU0sQ0FBQyxDQUFDLENBQzdGQyxPQUFPLENBQUMsVUFBQzZGLFNBQVMsRUFBSztNQUN0QixJQUFJQSxTQUFTLENBQUM2QyxJQUFJLElBQUk3QyxTQUFTLENBQUM4QyxHQUFHLEtBQUssWUFBWSxFQUFFO1FBQ3BESixNQUFNLENBQUNDLElBQUksQ0FBQzdJLElBQUksQ0FBQ2lKLGNBQWMsQ0FBQy9DLFNBQVMsQ0FBQzZDLElBQUksQ0FBQyxDQUFDO01BQ2xEO0lBQ0YsQ0FBQyxDQUFDO0lBRU4sT0FBTyxJQUFJeEosT0FBTyxDQUFDLFVBQUNDLE9BQU8sRUFBSztNQUM5QkQsT0FBTyxDQUFDMkosR0FBRyxDQUFDTixNQUFNLENBQUMsQ0FDZG5KLElBQUksQ0FBQyxZQUFNO1FBQ1YyQyxVQUFVLENBQUMsWUFBTTtVQUNmMkQsS0FBSyxDQUFDQyxJQUFJLENBQUNoRyxJQUFJLENBQUNrRCxnQkFBZ0IsQ0FBQytDLGdCQUFnQixDQUFDTixzQkFBc0IsQ0FBQ3ZGLGdCQUFnQixDQUFDLFFBQVEsQ0FBQyxDQUFDLENBQy9GQyxPQUFPLENBQUMsVUFBQzZGLFNBQVMsRUFBSztZQUN0QixJQUFJLENBQUNBLFNBQVMsQ0FBQzFELEdBQUcsRUFBRTtjQUNsQixJQUFNMkcsU0FBUyxHQUFHaEosUUFBUSxDQUFDb0MsYUFBYSxDQUFDLFFBQVEsQ0FBQztjQUNsRHdELEtBQUssQ0FBQ0MsSUFBSSxDQUFDRSxTQUFTLENBQUNrRCxVQUFVLENBQUMsQ0FDM0IvSSxPQUFPLENBQUMsVUFBQ2dKLElBQUk7Z0JBQUEsT0FBS0YsU0FBUyxDQUFDcEMsWUFBWSxDQUFDc0MsSUFBSSxDQUFDQyxJQUFJLEVBQUVELElBQUksQ0FBQy9LLEtBQUssQ0FBQztjQUFBLEVBQUM7Y0FDckU2SyxTQUFTLENBQUNyRyxXQUFXLENBQUMzQyxRQUFRLENBQUNvSixjQUFjLENBQUNyRCxTQUFTLENBQUNKLFNBQVMsQ0FBQyxDQUFDO2NBQ25FSSxTQUFTLENBQUNzRCxVQUFVLENBQUNDLFlBQVksQ0FBQ04sU0FBUyxFQUFFakQsU0FBUyxDQUFDO2NBQ3ZEbEcsSUFBSSxDQUFDbUcsVUFBVSxDQUFDRCxTQUFTLENBQUNKLFNBQVMsQ0FBQztZQUN0QztVQUNGLENBQUMsQ0FBQztVQUNOdEcsT0FBTyxDQUFDLENBQUM7UUFDWCxDQUFDLEVBQUUsQ0FBQyxDQUFDO01BQ1AsQ0FBQyxDQUFDO0lBQ1IsQ0FBQyxDQUFDO0VBQ0osQ0FBQztFQUVEUSxJQUFJLENBQUNtRyxVQUFVLEdBQUcsVUFBU3dDLE9BQU8sRUFBRTtJQUNsQyxPQUFPLElBQUlwSixPQUFPLENBQUMsVUFBQ0MsT0FBTyxFQUFLO01BQzlCa0ssSUFBSSxDQUFDZixPQUFPLENBQUM7TUFDYm5KLE9BQU8sQ0FBQyxDQUFDO0lBQ1gsQ0FBQyxDQUFDO0VBQ0osQ0FBQztFQUVEUSxJQUFJLENBQUM4SSxVQUFVLEdBQUcsVUFBU3RHLEdBQUcsRUFBRTtJQUM5QixPQUFPLElBQUlqRCxPQUFPLENBQUMsVUFBQ0MsT0FBTyxFQUFFbUssTUFBTSxFQUFLO01BQ3RDLElBQU1DLE1BQU0sR0FBR3pKLFFBQVEsQ0FBQ29DLGFBQWEsQ0FBQyxRQUFRLENBQUM7TUFDL0NxSCxNQUFNLENBQUNwSCxHQUFHLEdBQUdBLEdBQUc7TUFDaEJvSCxNQUFNLENBQUNDLElBQUksR0FBRyxpQkFBaUI7TUFFL0JELE1BQU0sQ0FBQ2xILE1BQU0sR0FBRztRQUFBLE9BQU1sRCxPQUFPLENBQUNvSyxNQUFNLENBQUM7TUFBQTtNQUNyQ0EsTUFBTSxDQUFDRSxPQUFPLEdBQUc7UUFBQSxPQUFNSCxNQUFNLENBQUMsSUFBSUksS0FBSyx5QkFBQWhJLE1BQUEsQ0FBeUJTLEdBQUcsQ0FBRSxDQUFDLENBQUM7TUFBQTtNQUV2RXJDLFFBQVEsQ0FBQzZKLElBQUksQ0FBQ0MsTUFBTSxDQUFDTCxNQUFNLENBQUM7TUFDNUJwSyxPQUFPLENBQUMsQ0FBQztJQUNYLENBQUMsQ0FBQztFQUNKLENBQUM7RUFFRFEsSUFBSSxDQUFDaUosY0FBYyxHQUFHLFVBQVN6RyxHQUFHLEVBQUU7SUFDbEMsT0FBTyxJQUFJakQsT0FBTyxDQUFDLFVBQUNDLE9BQU8sRUFBRW1LLE1BQU0sRUFBSztNQUN0QyxJQUFNckosSUFBSSxHQUFHSCxRQUFRLENBQUNvQyxhQUFhLENBQUMsTUFBTSxDQUFDO01BQzNDakMsSUFBSSxDQUFDeUksSUFBSSxHQUFHdkcsR0FBRztNQUNmbEMsSUFBSSxDQUFDMEksR0FBRyxHQUFHLFlBQVk7TUFFdkIxSSxJQUFJLENBQUNvQyxNQUFNLEdBQUc7UUFBQSxPQUFNbEQsT0FBTyxDQUFDYyxJQUFJLENBQUM7TUFBQTtNQUNqQ0EsSUFBSSxDQUFDd0osT0FBTyxHQUFHO1FBQUEsT0FBTUgsTUFBTSxDQUFDLElBQUlJLEtBQUsseUJBQUFoSSxNQUFBLENBQXlCUyxHQUFHLENBQUUsQ0FBQyxDQUFDO01BQUE7TUFFckVyQyxRQUFRLENBQUM2SixJQUFJLENBQUNDLE1BQU0sQ0FBQzNKLElBQUksQ0FBQztNQUMxQmQsT0FBTyxDQUFDLENBQUM7SUFDWCxDQUFDLENBQUM7RUFDSixDQUFDO0VBRURRLElBQUksQ0FBQzJELGVBQWUsR0FBRyxVQUFVVSxLQUFLLEVBQUU7SUFDdENBLEtBQUssQ0FBQzZGLGVBQWUsQ0FBQyxDQUFDO0lBQ3ZCN0YsS0FBSyxDQUFDOEYsY0FBYyxDQUFDLENBQUM7SUFDdEIsSUFBTXpHLFNBQVMsR0FBR1csS0FBSyxDQUFDeUMsTUFBTTtJQUM5QixJQUFNdEMsVUFBVSxHQUFHZCxTQUFTLENBQUNlLE9BQU8sQ0FBQyxtQkFBbUIsQ0FBQztJQUN6RCxJQUFJNEMsUUFBUSxHQUFHa0IsUUFBUSxDQUFDL0QsVUFBVSxDQUFDdkQsT0FBTyxDQUFDb0csUUFBUSxDQUFDO0lBQ3BEN0MsVUFBVSxDQUFDdkQsT0FBTyxDQUFDb0csUUFBUSxHQUFHLEVBQUVBLFFBQVE7SUFDeENySCxJQUFJLENBQUNtRixXQUFXLENBQUN6QixTQUFTLENBQUN6QyxPQUFPLEVBQUVqQixJQUFJLENBQUNvSyxjQUFjLENBQUMsQ0FBQyxDQUFDO0lBQzFELElBQUlqSyxRQUFRLENBQUNrQyxhQUFhLENBQUMseUJBQXlCLENBQUMsRUFBRTtNQUNyRGxDLFFBQVEsQ0FBQ2tDLGFBQWEsQ0FBQyx5QkFBeUIsQ0FBQyxDQUFDTyxNQUFNLENBQUMsQ0FBQztJQUM1RDtJQUVBLElBQU15SCxTQUFTLEdBQUdsSyxRQUFRLENBQUNrQyxhQUFhLENBQUMsYUFBYSxDQUFDO0lBQ3ZELElBQU1pSSxLQUFLLEdBQUczSSxTQUFTLENBQUM0SSxLQUFLLENBQUNDLG1CQUFtQixDQUFDSCxTQUFTLENBQUM7SUFDNURDLEtBQUssQ0FBQ3RILElBQUksQ0FBQyxDQUFDO0lBQ1osT0FBTyxLQUFLO0VBQ2QsQ0FBQztFQUVEaEQsSUFBSSxDQUFDb0ssY0FBYyxHQUFHLFlBQVk7SUFDaEMsSUFBSUssRUFBRSxHQUFHLElBQUlDLElBQUksQ0FBQyxDQUFDLENBQUNDLE9BQU8sQ0FBQyxDQUFDO0lBQzdCLElBQU1DLElBQUksR0FBRyxVQUFVLENBQUM5QyxPQUFPLENBQUMsT0FBTyxFQUFFLFVBQUM1SyxDQUFDLEVBQUs7TUFDOUMsSUFBTVAsQ0FBQyxHQUFHLENBQUM4TixFQUFFLEdBQUd2SixJQUFJLENBQUMySixNQUFNLENBQUMsQ0FBQyxHQUFDLEVBQUUsSUFBRSxFQUFFLEdBQUcsQ0FBQztNQUN4Q0osRUFBRSxHQUFHdkosSUFBSSxDQUFDNEosS0FBSyxDQUFDTCxFQUFFLEdBQUMsRUFBRSxDQUFDO01BQ3RCLE9BQU8sQ0FBQ3ZOLENBQUMsSUFBRSxHQUFHLEdBQUdQLENBQUMsR0FBR0EsQ0FBQyxHQUFDLEdBQUcsR0FBQyxHQUFJLEVBQUVvTyxRQUFRLENBQUMsRUFBRSxDQUFDO0lBQy9DLENBQUMsQ0FBQztJQUNGLE9BQU9ILElBQUk7RUFDYixDQUFDO0VBRUQ1SyxJQUFJLENBQUNxRCw0QkFBNEIsR0FBRyxVQUFVZ0IsS0FBSyxFQUFFO0lBQ25ELElBQU0yRyxjQUFjLEdBQUczRyxLQUFLLENBQUN5QyxNQUFNLENBQUN4SSxLQUFLO0lBQ3pDK0YsS0FBSyxDQUFDeUMsTUFBTSxDQUFDckMsT0FBTyxDQUFDLG9CQUFvQixDQUFDLENBQ3JDckUsZ0JBQWdCLENBQUMsdUJBQXVCLENBQUMsQ0FDekNDLE9BQU8sQ0FBQyxVQUFDNEssSUFBSSxFQUFLO01BQ2pCQSxJQUFJLENBQUN4SSxLQUFLLENBQUMrRCxPQUFPLEdBQUd3RSxjQUFjLEtBQUssSUFBSSxJQUFJQSxjQUFjLEtBQUssWUFBWSxHQUFHLE9BQU8sR0FBRyxNQUFNO0lBQ3BHLENBQUMsQ0FBQztJQUNOLElBQUlBLGNBQWMsRUFBRTtNQUNsQjNHLEtBQUssQ0FBQ3lDLE1BQU0sQ0FBQ3JDLE9BQU8sQ0FBQyxvQkFBb0IsQ0FBQyxDQUNyQ3JFLGdCQUFnQiwyQkFBQTJCLE1BQUEsQ0FBMEJpSixjQUFjLFFBQUksQ0FBQyxDQUM3RDNLLE9BQU8sQ0FBQyxVQUFDNEssSUFBSSxFQUFLO1FBQ2pCQSxJQUFJLENBQUN4SSxLQUFLLENBQUMrRCxPQUFPLEdBQUcsT0FBTztNQUM5QixDQUFDLENBQUM7SUFDUjtFQUNGLENBQUM7RUFFRHhHLElBQUksQ0FBQ3VELHdCQUF3QixHQUFHLFVBQVVjLEtBQUssRUFBRTtJQUMvQyxJQUFNNkcsTUFBTSxHQUFHN0csS0FBSyxDQUFDeUMsTUFBTSxDQUFDeEksS0FBSztJQUMvQitGLEtBQUssQ0FBQ3lDLE1BQU0sQ0FBQ3JDLE9BQU8sQ0FBQyxvQkFBb0IsQ0FBQyxDQUNyQ3JFLGdCQUFnQixvQ0FBb0MsQ0FBQyxDQUNyREMsT0FBTyxDQUFDLFVBQUM4SyxLQUFLLEVBQUs7TUFDaEIsSUFBTUMsS0FBSyxHQUFHLElBQUl6RCxNQUFNLENBQUN1RCxNQUFNLEVBQUUsR0FBRyxDQUFDO01BQ3JDQyxLQUFLLENBQUMvQyxhQUFhLENBQUNBLGFBQWEsQ0FBQzNGLEtBQUssQ0FBQytELE9BQU8sR0FBRzRFLEtBQUssQ0FBQ0MsSUFBSSxDQUFDRixLQUFLLENBQUNHLFNBQVMsQ0FBQyxJQUFJLENBQUNKLE1BQU0sR0FBRyxPQUFPLEdBQUcsTUFBTTtJQUMvRyxDQUFDLENBQUM7RUFDVixDQUFDO0VBRURqSSxVQUFVLENBQUMsQ0FBQztFQUNaL0MsdUJBQXVCLENBQUMsQ0FBQztBQUMzQixDQUFDO0FBRURvQixNQUFNLENBQUNkLGdCQUFnQixDQUFDLGtCQUFrQixFQUFFVCxxQkFBcUIsQ0FBQyxDIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQGFnZW5jZS1hZGVsaW9tL3N5bGl1cy1oYXBweS1jbXMtcGx1Z2luLy4vYXNzZXRzL2ZsZXhpYmxlLWNvbnRlbnQvZmxleGlibGUtY29udGVudC5jc3M/ZTE1NSIsIndlYnBhY2s6Ly9AYWdlbmNlLWFkZWxpb20vc3lsaXVzLWhhcHB5LWNtcy1wbHVnaW4vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vQGFnZW5jZS1hZGVsaW9tL3N5bGl1cy1oYXBweS1jbXMtcGx1Z2luL3dlYnBhY2svcnVudGltZS9tYWtlIG5hbWVzcGFjZSBvYmplY3QiLCJ3ZWJwYWNrOi8vQGFnZW5jZS1hZGVsaW9tL3N5bGl1cy1oYXBweS1jbXMtcGx1Z2luLy4vYXNzZXRzL2ZsZXhpYmxlLWNvbnRlbnQvZmxleGlibGUtY29udGVudC5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiLCIvLyBUaGUgbW9kdWxlIGNhY2hlXG52YXIgX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fID0ge307XG5cbi8vIFRoZSByZXF1aXJlIGZ1bmN0aW9uXG5mdW5jdGlvbiBfX3dlYnBhY2tfcmVxdWlyZV9fKG1vZHVsZUlkKSB7XG5cdC8vIENoZWNrIGlmIG1vZHVsZSBpcyBpbiBjYWNoZVxuXHR2YXIgY2FjaGVkTW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXTtcblx0aWYgKGNhY2hlZE1vZHVsZSAhPT0gdW5kZWZpbmVkKSB7XG5cdFx0cmV0dXJuIGNhY2hlZE1vZHVsZS5leHBvcnRzO1xuXHR9XG5cdC8vIENyZWF0ZSBhIG5ldyBtb2R1bGUgKGFuZCBwdXQgaXQgaW50byB0aGUgY2FjaGUpXG5cdHZhciBtb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdID0ge1xuXHRcdC8vIG5vIG1vZHVsZS5pZCBuZWVkZWRcblx0XHQvLyBubyBtb2R1bGUubG9hZGVkIG5lZWRlZFxuXHRcdGV4cG9ydHM6IHt9XG5cdH07XG5cblx0Ly8gRXhlY3V0ZSB0aGUgbW9kdWxlIGZ1bmN0aW9uXG5cdGlmICghKG1vZHVsZUlkIGluIF9fd2VicGFja19tb2R1bGVzX18pKSB7XG5cdFx0ZGVsZXRlIF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF07XG5cdFx0dmFyIGUgPSBuZXcgRXJyb3IoXCJDYW5ub3QgZmluZCBtb2R1bGUgJ1wiICsgbW9kdWxlSWQgKyBcIidcIik7XG5cdFx0ZS5jb2RlID0gJ01PRFVMRV9OT1RfRk9VTkQnO1xuXHRcdHRocm93IGU7XG5cdH1cblx0X193ZWJwYWNrX21vZHVsZXNfX1ttb2R1bGVJZF0obW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cblx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcblx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xufVxuXG4iLCIvLyBkZWZpbmUgX19lc01vZHVsZSBvbiBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLnIgPSAoZXhwb3J0cykgPT4ge1xuXHRpZih0eXBlb2YgU3ltYm9sICE9PSAndW5kZWZpbmVkJyAmJiBTeW1ib2wudG9TdHJpbmdUYWcpIHtcblx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgU3ltYm9sLnRvU3RyaW5nVGFnLCB7IHZhbHVlOiAnTW9kdWxlJyB9KTtcblx0fVxuXHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgJ19fZXNNb2R1bGUnLCB7IHZhbHVlOiB0cnVlIH0pO1xufTsiLCJpbXBvcnQgJy4vZmxleGlibGUtY29udGVudC5jc3MnO1xuXG5jb25zdCBmbGV4aWJsZUNvbnRlbnRNb2R1bGUgPSBmdW5jdGlvbiAoKSB7XG4gIGNvbnN0IHNlbGYgPSB0aGlzO1xuXG4gIHNlbGYuYmxvY2tUb01vdmUgPSBudWxsO1xuXG4gIGNvbnN0IGluaXRJZnJhbWVQcmV2aWV3TW9kdWxlID0gZnVuY3Rpb24gKCkge1xuICAgICAgZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnLmlmcmFtZS10b29sdGlwJykuZm9yRWFjaChsaW5rID0+IHtcbiAgICAgICAgICBsZXQgdG9vbHRpcEluc3RhbmNlID0gbnVsbDtcblxuICAgICAgICAgIGxpbmsuYWRkRXZlbnRMaXN0ZW5lcignbW91c2VlbnRlcicsIGFzeW5jICgpID0+IHtcbiAgICAgICAgICAgICAgY29uc3QgdXJsID0gbGluay5kYXRhc2V0LnVybDtcblxuICAgICAgICAgICAgICAvKlxuICAgICAgICAgICAgICAgIFN1ciB1biDDqWNyYW4gZGVza3RvcCAxOTIweDEwODAgOiA2MDAgeCA0MDBcbiAgICAgICAgICAgICAgICBTdXIgdW4gw6ljcmFuIHRhYmxldCA3Njh4MTAyNCA6IDQ2MCB4IDQwMFxuICAgICAgICAgICAgICAgIFN1ciB1biBtb2JpbGUgKDM2MHg2NDApIDogMjE2IHggMjU2XG4gICAgICAgICAgICAgICAqL1xuICAgICAgICAgICAgICBjb25zdCB2dyA9IE1hdGgubWF4KGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5jbGllbnRXaWR0aCB8fCAwLCB3aW5kb3cuaW5uZXJXaWR0aCB8fCAwKTtcbiAgICAgICAgICAgICAgY29uc3QgdmggPSBNYXRoLm1heChkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuY2xpZW50SGVpZ2h0IHx8IDAsIHdpbmRvdy5pbm5lckhlaWdodCB8fCAwKTtcbiAgICAgICAgICAgICAgICAvLyBEaW1lbnNpb25zIHJlc3BvbnNpdmVzIHBhciBkw6lmYXV0XG4gICAgICAgICAgICAgIGNvbnN0IHdpZHRoID0gbGluay5kYXRhc2V0LndpZHRoIHx8IE1hdGgubWluKDAuNiAqIHZ3LCA2MDApOyAvLyBtYXggNjAwcHggb3UgNjAlIGR1IHZpZXdwb3J0XG4gICAgICAgICAgICAgIGNvbnN0IGhlaWdodCA9IGxpbmsuZGF0YXNldC5oZWlnaHQgfHwgTWF0aC5taW4oMC40ICogdmgsIDQwMCk7IC8vIG1heCA0MDBweCBvdSA0MCUgZHUgdmlld3BvcnRcblxuICAgICAgICAgICAgICBjb25zdCB0b29sdGlwID0gbmV3IGJvb3RzdHJhcC5Ub29sdGlwKGxpbmssIHtcbiAgICAgICAgICAgICAgICAgIGh0bWw6IHRydWUsXG4gICAgICAgICAgICAgICAgICB0ZW1wbGF0ZTogYDxkaXYgY2xhc3M9XCJmbGV4aWJsZS10b29sdGlwXCIgcm9sZT1cInRvb2x0aXBcIj48ZGl2IGNsYXNzPVwiaWZyYW1lLWNvbnRhaW5lclwiIHN0eWxlPVwid2lkdGg6ICR7d2lkdGh9cHg7IGhlaWdodDogJHtoZWlnaHR9cHg7XCI+XG4gICAgICAgICAgICAgICAgPGRpdiBjbGFzcz1cInNwaW5uZXItZ3JvdyB0ZXh0LXByaW1hcnlcIiByb2xlPVwic3RhdHVzXCI+XG4gIDxzcGFuIGNsYXNzPVwidmlzdWFsbHktaGlkZGVuXCI+TG9hZGluZy4uLjwvc3Bhbj5cbjwvZGl2PlxuICAgICAgICAgICAgICA8L2Rpdj48L2Rpdj5gLFxuICAgICAgICAgICAgICAgICAgcGxhY2VtZW50OiAnbGVmdCcsXG4gICAgICAgICAgICAgICAgICB0cmlnZ2VyOiAnbWFudWFsJyxcbiAgICAgICAgICAgICAgICAgIGNvbnRhaW5lcjogJ2JvZHknXG4gICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgIHRvb2x0aXAuc2hvdygpO1xuXG4gICAgICAgICAgICAgIC8vIEluc2VydGlvbiBkeW5hbWlxdWUgZGUgbOKAmWlmcmFtZSBhcHLDqHMgYWZmaWNoYWdlIGR1IHRvb2x0aXBcbiAgICAgICAgICAgICAgc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICAgICAgICAgICBjb25zdCBjb250YWluZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcuZmxleGlibGUtdG9vbHRpcCAuaWZyYW1lLWNvbnRhaW5lcicpO1xuICAgICAgICAgICAgICAgICAgaWYgKGNvbnRhaW5lciAmJiAhY29udGFpbmVyLnF1ZXJ5U2VsZWN0b3IoJ2lmcmFtZScpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgY29uc3QgaWZyYW1lID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaWZyYW1lJyk7XG4gICAgICAgICAgICAgICAgICAgICAgaWZyYW1lLnNyYyA9IHVybDtcbiAgICAgICAgICAgICAgICAgICAgICBpZnJhbWUuc3R5bGUud2lkdGggPSB3aWR0aCsncHgnO1xuICAgICAgICAgICAgICAgICAgICAgIGlmcmFtZS5zdHlsZS5oZWlnaHQgPSBoZWlnaHQrJ3B4JztcblxuICAgICAgICAgICAgICAgICAgICAgIGlmcmFtZS5vbmxvYWQgPSAoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IGxvYWRlciA9IGNvbnRhaW5lci5xdWVyeVNlbGVjdG9yKCcuc3Bpbm5lci1ncm93Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChsb2FkZXIpIGxvYWRlci5yZW1vdmUoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgaWZyYW1lLnN0eWxlLm9wYWNpdHkgPSAnMSc7XG4gICAgICAgICAgICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgICAgICAgICAgIGNvbnRhaW5lci5hcHBlbmRDaGlsZChpZnJhbWUpO1xuICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB9LCAxMDApO1xuICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgbGluay5hZGRFdmVudExpc3RlbmVyKCdtb3VzZWxlYXZlJywgKCkgPT4ge1xuICAgICAgICAgICAgICBjb25zdCB0b29sdGlwID0gYm9vdHN0cmFwLlRvb2x0aXAuZ2V0SW5zdGFuY2UobGluayk7XG4gICAgICAgICAgICAgIGlmICh0b29sdGlwKSB7XG4gICAgICAgICAgICAgICAgICB0b29sdGlwLmhpZGUoKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgIH0pO1xuICAgICAgfSk7XG4gIH07XG5cbiAgY29uc3QgaW5pdE1vZHVsZSA9IGZ1bmN0aW9uICgpIHtcbiAgICAvLyBBdSBjaGFyZ2VtZW50IG9uIHZhIGluaXRpYWxpc2VyIGxlIGNvbXBvcnRlbWVudCBsacOpIGF1IGNhdGFsb2d1ZSBkZXMgYmxvY3MgOlxuXG4gICAgLy8gICAgLSBJZGVudGlmaWVyIGxlIHdyYXBwZXIgZGUgbGEgY29sb25uZSBkZSBnYXVjaGVcbiAgICBzZWxmLndGbGV4aWJsZUNvbnRlbnQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcudy1mbGV4aWJsZS1jb250ZW50Jyk7XG5cbiAgICAvLyAgICAtIElkZW50aWZpZXIgbGUgd3JhcHBlciBkZSBsYSBjb2xvbm5lIGRlIGRyb2l0ZVxuICAgIHNlbGYud0ZsZXhpYmxlQmxvY2sgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcudy1mbGV4aWJsZS1ibG9ja3MnKTtcblxuICAgIC8vICAgIC0gbGUgZHJvcGRvd24gZGVzIGNhdMOpZ29yaWVzIChhdSBjaG9peCkgOiBtYXNxdWVyIHRvdXMgbGVzIGJsb2NzIHNhdWYgY2V1eCBkZW1hbmTDqXNcbiAgICBjb25zdCBkcm9wRG93bkJsb2NrQ2F0ZWdvcmllcyA9IHNlbGYud0ZsZXhpYmxlQmxvY2sucXVlcnlTZWxlY3RvcignLmJsb2NrLWNhdGVnb3JpZXMnKTtcbiAgICBkcm9wRG93bkJsb2NrQ2F0ZWdvcmllcy5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBzZWxmLmxpc3RlbkJsb2NrQ2F0ZWdvcmllc0NoYW5nZXMpO1xuXG4gICAgLy8gICAgLSBsZSBkcm9wZG93biBkZXMgY2F0w6lnb3JpZXMgKGF1IGNob2l4KSA6IG1hc3F1ZXIgdG91cyBsZXMgYmxvY3Mgc2F1ZiBjZXV4IGRlbWFuZMOpc1xuICAgIGNvbnN0IGZpbHRlckJsb2NrcyA9IHNlbGYud0ZsZXhpYmxlQmxvY2sucXVlcnlTZWxlY3RvcignI2Jsb2NrLWZpbHRlcicpO1xuICAgICAgZmlsdGVyQmxvY2tzLmFkZEV2ZW50TGlzdGVuZXIoJ2tleXVwJywgc2VsZi5saXN0ZW5CbG9ja0ZpbHRlckNoYW5nZXMpO1xuXG4gICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29wZW4tYmxvY2tzJykuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9PiB7XG4gICAgICAgIHNldFRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgc2VsZi53RmxleGlibGVCbG9jay5xdWVyeVNlbGVjdG9yKCcjYmxvY2stZmlsdGVyJykuZm9jdXMoKTtcbiAgICAgICAgfSwgMzAwKVxuICAgIH0pO1xuXG4gICAgLy8gICAgLSBsZSBib3V0b24gYWpvdXRlclxuICAgIHNlbGYud0ZsZXhpYmxlQmxvY2sucXVlcnlTZWxlY3RvckFsbCgnYS5hZGQtZmxleGlibGUtYmxvY2snKVxuICAgICAgICAuZm9yRWFjaCgoYWRkQnV0dG9uKSA9PiB7XG4gICAgICAgICAgYWRkQnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgc2VsZi5oYW5kbGVBZGRCdXR0b24pO1xuICAgICAgICB9KTtcblxuICAgIC8vICAgIC0gSW5pdGlhbGlzZXIgbGVzIGNvbXBvcnRlbWVudHMgZGVzIGJsb2NzIGV4aXN0YW50c1xuICAgIHNlbGYud0ZsZXhpYmxlQ29udGVudC5xdWVyeVNlbGVjdG9yQWxsKCcuYmxvYy13cmFwcGVyJylcbiAgICAgICAgLmZvckVhY2goKGJsb2NrKSA9PiB7XG4gICAgICAgICAgc2VsZi5oYW5kbGVCbG9jayhibG9jayk7XG4gICAgICAgIH0pO1xuXG4gICAgc2VsZi53RmxleGlibGVDb250ZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5tb3ZlLWhlcmUnKS5mb3JFYWNoKGVsID0+IHtcbiAgICAgIHNlbGYuaGFuZGxlQ2xpY2tNb3ZlKGVsKTtcbiAgICB9KTtcblxuICAgIC8vICAgIC0gSW5pdGlhbGlzZXIgbGVzIHBvc2l0aW9ucyBkZXMgYmxvY2tzXG4gICAgc2VsZi5yZWNhbGN1bGF0ZUJsb2NrUG9zaXRpb25zKCk7XG4gIH07XG5cbiAgc2VsZi5oYW5kbGVDbGlja01vdmUgPSBmdW5jdGlvbihlbCkge1xuICAgIGVsLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24oZXYpIHtcbiAgICAgIGlmIChzZWxmLmJsb2NrVG9Nb3ZlICE9PSBudWxsKSB7XG4gICAgICAgIC8vIGNoYW5nZSBwb3NpdGlvbnNcbiAgICAgICAgY29uc3QgbmV4dE1vdmVMYXllciA9IHNlbGYuYmxvY2tUb01vdmUubmV4dEVsZW1lbnRTaWJsaW5nO1xuICAgICAgICBlbC5hZnRlcihzZWxmLmJsb2NrVG9Nb3ZlKTtcbiAgICAgICAgc2VsZi5ibG9ja1RvTW92ZS5hZnRlcihuZXh0TW92ZUxheWVyKTtcbiAgICAgICAgLy8gY2hhbmdlIGJsb2NrIHBvc2l0aW9uIHZhbHVlXG4gICAgICAgIHNlbGYucmVjYWxjdWxhdGVCbG9ja1Bvc2l0aW9ucygpO1xuICAgICAgICAvLyBkaXNhYmxlIGFjdGl2ZSBtb3ZlIGJlaGF2aW9yXG4gICAgICAgIGNvbnN0IGV2ZW50ID0gbmV3IEV2ZW50KFwiY2xpY2tcIik7XG4gICAgICAgIHNlbGYuYmxvY2tUb01vdmVcbiAgICAgICAgICAgIC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1hY3Rpb249XCJtb3ZlXCJdJylcbiAgICAgICAgICAgIC5kaXNwYXRjaEV2ZW50KGV2ZW50KTtcbiAgICAgIH1cbiAgICB9KTtcbiAgfTtcblxuICBzZWxmLnJlY2FsY3VsYXRlQmxvY2tQb3NpdGlvbnMgPSBmdW5jdGlvbigpIHtcbiAgICBjb25zdCBjb2xsZWN0aW9uID0gc2VsZi53RmxleGlibGVDb250ZW50LmNsb3Nlc3QoJ1tkYXRhLXN5bGl1cy1mbGV4aWJsZS1jb250ZW50LWZpZWxkXScpO1xuICAgIGNvbnN0IGJsb2NrUG9zaXRpb25JbnB1dHMgPSBjb2xsZWN0aW9uXG4gICAgICAgIC5xdWVyeVNlbGVjdG9yQWxsKCcuYmxvYy13cmFwcGVyIFtkYXRhLWxheWVyPVwiY29udGVudFwiXSBpbnB1dFt0eXBlPVwiaGlkZGVuXCJdJyk7XG4gICAgbGV0IGNvdW50ID0gc2VsZi53RmxleGlibGVDb250ZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5ibG9jLXdyYXBwZXInKS5sZW5ndGg7XG4gICAgYmxvY2tQb3NpdGlvbklucHV0cy5mb3JFYWNoKChmaWVsZCkgPT4ge1xuICAgICAgaWYgKGZpZWxkLmlkLmluY2x1ZGVzKFwiX3Bvc2l0aW9uXCIpKSB7XG4gICAgICAgIGZpZWxkLnZhbHVlID0gY291bnQ7XG4gICAgICAgIGNvdW50ICsrO1xuICAgICAgfVxuICAgIH0pO1xuICB9O1xuXG4gIHNlbGYuaGFuZGxlQmxvY2sgPSBmdW5jdGlvbihibG9jaykge1xuICAgIC8vICAgIC0gY2xpY2sgb3BlbiAvIGNsb3NlXG4gICAgc2VsZi5oYW5kbGVUb2dnbGVDb250ZW50KGJsb2NrKTtcbiAgICAvLyAgICAtIGNsaWNrIG1vdmVcbiAgICBzZWxmLmhhbmRsZU1vdmVDb250ZW50KGJsb2NrKTtcbiAgICAvLyAgICAtIGNsaWNrICsgYWxlcnQgdHJhc2hcbiAgICBzZWxmLmhhbmRsZVJlbW92ZUNvbnRlbnQoYmxvY2spO1xuICAgIC8vICAgIC0gcHVibGlzaFxuICAgIHNlbGYuaGFuZGxlUHVibGlzaENvbnRlbnQoYmxvY2spO1xuICB9O1xuXG4gIHNlbGYuYWRkTmV3QmxvY2sgPSBmdW5jdGlvbiAoZGF0YSwgaW5kZXgpIHtcbiAgICAvLyBMb3JzcXUnb24gYWpvdXRlIHVuIGJsb2MgaWwgZmF1dCBpbml0aWxpc2VyIHNvbiBjb21wb3J0ZW1lbnRcbiAgICAvLyAgICAtIFLDqWN1cMOpcmVyIGxlIHByb3RvdHlwZSBkdSB3cmFwcGVyIGR1IGZ1dHVyIGJsb2NrXG4gICAgY29uc3QgYmxvY2tXcmFwcGVyID0gc2VsZi5nZXRCbG9ja1dyYXBwZXJQcm90b3R5cGUoKTtcbiAgICAvLyAgICAtIFkgaW5qZWN0ZXIgbGUgcHJvdG90eXBlIGR1IGJsb2NrXG4gICAgY29uc3QgbmV3Q29udGVudHMgPSBzZWxmLmdlbmVyYXRlTmV3QmxvY2soYmxvY2tXcmFwcGVyLCBkYXRhLCBpbmRleCk7XG4gICAgc2VsZi53RmxleGlibGVDb250ZW50LnN0eWxlLm9wYWNpdHkgPSAwO1xuICAgIHNlbGYuYXBwZW5kTmV3QmxvY2sobmV3Q29udGVudHNbMF0pXG4gICAgICAgIC50aGVuKCgpID0+IHtcbiAgICAgICAgICBjb25zdCBibG9jayA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCd3LXdyYXBwZXItcHJvdG90eXBlJykucHJldmlvdXNFbGVtZW50U2libGluZztcbiAgICAgICAgICBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgndy13cmFwcGVyLXByb3RvdHlwZScpLmJlZm9yZShuZXdDb250ZW50c1sxXSk7XG4gICAgICAgICAgY29uc3QgeyBibG9ja05hbWUgfSA9IGRhdGE7XG4gICAgICAgICAgLy8gICAgLSBSZW1wbGFjZXIgbGUgdGl0cmUgcGFyIGxlIG5vbSBkdSBibG9jXG4gICAgICAgICAgYmxvY2sucXVlcnlTZWxlY3RvcignW2RhdGEtbGF5ZXI9XCJ0aXRsZVwiXScpLmlubmVySFRNTCA9IGJsb2NrTmFtZTtcbiAgICAgICAgICAvLyAgICAtIEluaXQgYmxvY2sgZXZlbnRzXG4gICAgICAgICAgc2VsZi5oYW5kbGVCbG9jayhibG9jayk7XG4gICAgICAgICAgLy8gICAgLSBSZWNhbGN1bGVyIGxlcyBwb3NpdGlvbnNcbiAgICAgICAgICBzZWxmLnJlY2FsY3VsYXRlQmxvY2tQb3NpdGlvbnMoKTtcbiAgICAgICAgICAvLyAgICAtIGV4ZWN1dGVyIGxlcyBzY3JpcHRzIGpzXG4gICAgICAgICAgQXJyYXkuZnJvbShzZWxmLndGbGV4aWJsZUNvbnRlbnQubGFzdEVsZW1lbnRDaGlsZC5xdWVyeVNlbGVjdG9yQWxsKCdzY3JpcHQnKSlcbiAgICAgICAgICAgICAgLmZvckVhY2goKG9sZFNjcmlwdCkgPT4ge1xuICAgICAgICAgICAgICAgIGlmICghb2xkU2NyaXB0LnNyYykge1xuICAgICAgICAgICAgICAgICAgc2VsZi5ldmFsU2NyaXB0KG9sZFNjcmlwdC5pbm5lckhUTUwpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgLyogZ2xvYmFsICQgKi9cbiAgICAgICAgICAvLyQoJy51aS5jaGVja2JveCcpLmNoZWNrYm94KCk7XG4gICAgICAgICAgc2VsZi53RmxleGlibGVDb250ZW50LnN0eWxlLm9wYWNpdHkgPSAxO1xuICAgICAgICB9KTtcbiAgfTtcblxuICAvLyBhY3Rpb24gbW92ZVxuICBzZWxmLmhhbmRsZU1vdmVDb250ZW50ID0gZnVuY3Rpb24gKGJsb2NrKSB7XG4gICAgICBmdW5jdGlvbiBoaWRlTW92ZUhlcmUobW92ZUJ1dHRvbikge1xuICAgICAgICAgIHNlbGYud0ZsZXhpYmxlQ29udGVudC5xdWVyeVNlbGVjdG9yQWxsKCcubW92ZS1oZXJlJykuZm9yRWFjaChlbCA9PiB7XG4gICAgICAgICAgICAgIGNvbnN0IG1vdmVFbmFibGVkID0gbW92ZUJ1dHRvbi5nZXRBdHRyaWJ1dGUoJ2NsaWNrZWQnKSA9PT0gXCJ0cnVlXCI7XG4gICAgICAgICAgICAgIHNlbGYuYmxvY2tUb01vdmUgPSBtb3ZlRW5hYmxlZCA/IGJsb2NrIDogbnVsbDtcbiAgICAgICAgICAgICAgZWwuc3R5bGUuZGlzcGxheSA9IG1vdmVFbmFibGVkID8gJ2Jsb2NrJyA6ICdub25lJztcbiAgICAgICAgICAgICAgaWYgKG1vdmVFbmFibGVkKSB7XG4gICAgICAgICAgICAgICAgICBzZWxmLmJsb2NrVG9Nb3ZlLnByZXZpb3VzRWxlbWVudFNpYmxpbmcuc3R5bGUuZGlzcGxheSA9ICdub25lJztcbiAgICAgICAgICAgICAgICAgIGlmIChzZWxmLmJsb2NrVG9Nb3ZlLm5leHRFbGVtZW50U2libGluZykge1xuICAgICAgICAgICAgICAgICAgICAgIHNlbGYuYmxvY2tUb01vdmUubmV4dEVsZW1lbnRTaWJsaW5nLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG4gICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICB9KTtcbiAgICAgIH1cblxuICAgICAgZnVuY3Rpb24gaGFuZGxlRXNjYXBlS2V5UHJlc3MoZSkge1xuICAgICAgICAgIGlmIChlLmtleSA9PT0gXCJFc2NhcGVcIikge1xuICAgICAgICAgICAgICBjb25zdCBtb3ZlQnV0dG9uID0gYmxvY2sucXVlcnlTZWxlY3RvcignW2RhdGEtYWN0aW9uPVwibW92ZVwiXScpO1xuICAgICAgICAgICAgICBtb3ZlQnV0dG9uLnJlbW92ZUF0dHJpYnV0ZSgnY2xpY2tlZCcpO1xuICAgICAgICAgICAgICBtb3ZlQnV0dG9uLmNsYXNzTGlzdC5yZW1vdmUoJ2JvcmRlci10ZWFsJyk7XG4gICAgICAgICAgICAgIGRvY3VtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoXCJrZXlkb3duXCIsIGhhbmRsZUVzY2FwZUtleVByZXNzKTtcbiAgICAgICAgICAgICAgaGlkZU1vdmVIZXJlKG1vdmVCdXR0b24pO1xuICAgICAgICAgIH1cbiAgICB9XG5cbiAgICBibG9jay5xdWVyeVNlbGVjdG9yKCdbZGF0YS1hY3Rpb249XCJtb3ZlXCJdJylcbiAgICAgICAgLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgICAgc2VsZi53RmxleGlibGVDb250ZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRhLWFjdGlvbj1cIm1vdmVcIl0uYm9yZGVyLXRlYWwnKS5mb3JFYWNoKGVsID0+IHsgaWYgKGVsICE9PSBldmVudC50YXJnZXQpIHsgZWwuY2xhc3NMaXN0LnJlbW92ZSgnYm9yZGVyLXRlYWwnKTsgfSB9KTtcbiAgICAgICAgICBjb25zdCBtb3ZlQnV0dG9uID0gYmxvY2sucXVlcnlTZWxlY3RvcignW2RhdGEtYWN0aW9uPVwibW92ZVwiXScpO1xuICAgICAgICAgIGlmIChtb3ZlQnV0dG9uLmdldEF0dHJpYnV0ZSgnY2xpY2tlZCcpID09PSBcInRydWVcIikge1xuICAgICAgICAgICAgICBkb2N1bWVudC5yZW1vdmVFdmVudExpc3RlbmVyKFwia2V5ZG93blwiLCBoYW5kbGVFc2NhcGVLZXlQcmVzcyk7XG4gICAgICAgICAgICAgIG1vdmVCdXR0b24ucmVtb3ZlQXR0cmlidXRlKCdjbGlja2VkJyk7XG4gICAgICAgICAgICAgIG1vdmVCdXR0b24uY2xhc3NMaXN0LnJlbW92ZSgnYm9yZGVyLXRlYWwnKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKFwia2V5ZG93blwiLCBoYW5kbGVFc2NhcGVLZXlQcmVzcyk7XG4gICAgICAgICAgICAgIG1vdmVCdXR0b24uc2V0QXR0cmlidXRlKCdjbGlja2VkJywgXCJ0cnVlXCIpO1xuICAgICAgICAgICAgICBtb3ZlQnV0dG9uLmNsYXNzTGlzdC5hZGQoJ2JvcmRlci10ZWFsJyk7XG4gICAgICAgICAgfVxuICAgICAgICAgIGhpZGVNb3ZlSGVyZShtb3ZlQnV0dG9uKTtcbiAgICAgICAgfSk7XG4gIH07XG5cbiAgLy8gYWN0aW9uIHdoZW4gY2hhbmdlIHB1Ymxpc2hlZCBjaGVja2JveFxuICBzZWxmLmhhbmRsZVB1Ymxpc2hDb250ZW50ID0gZnVuY3Rpb24gKGJsb2NrKSB7XG5cbiAgICAvLyBBcHBseSBjaGVja2VkIHN0YXRlIGJhc2VkIG9uIHB1Ymxpc2hlZCBoaWRkZW4gaW5wdXRcbiAgICBsZXQgYmxvY2tQdWJsaXNoZWRJbnB1dHMgPSBibG9ja1xuICAgICAgICAucXVlcnlTZWxlY3RvckFsbCgnaW5wdXRbdHlwZT1cImhpZGRlblwiXScpO1xuICAgIGJsb2NrUHVibGlzaGVkSW5wdXRzLmZvckVhY2goZnVuY3Rpb24gKGZpZWxkKSB7XG4gICAgICBpZiAoZmllbGQuaWQuaW5jbHVkZXMoXCJfYmxvY2tfcHVibGlzaGVkXCIpKSB7XG4gICAgICAgIGJsb2NrLnF1ZXJ5U2VsZWN0b3IoJy5ibG9ja19wdWJsaXNoZWQgW3R5cGU9XCJjaGVja2JveFwiXScpLmNoZWNrZWQgPSBmaWVsZC52YWx1ZSA9PT0gJzEnO1xuICAgICAgICBpZiAoZmllbGQudmFsdWUgPT09ICcxJykge1xuICAgICAgICAgIGJsb2NrLmNsYXNzTGlzdC5yZW1vdmUoJ2JvcmRlci1ncmF5Jyk7XG4gICAgICAgICAgYmxvY2suY2xhc3NMaXN0LmFkZCgnYm9yZGVyLXRlYWwnKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBibG9jay5jbGFzc0xpc3QuYWRkKCdib3JkZXItZ3JheScpO1xuICAgICAgICAgIGJsb2NrLmNsYXNzTGlzdC5yZW1vdmUoJ2JvcmRlci10ZWFsJyk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9KTtcblxuICAgIGJsb2NrLnF1ZXJ5U2VsZWN0b3IoJy5ibG9ja19wdWJsaXNoZWQgW3R5cGU9XCJjaGVja2JveFwiXScpXG4gICAgICAgIC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgICAgICBibG9ja1B1Ymxpc2hlZElucHV0cyA9IGJsb2NrXG4gICAgICAgICAgICAgIC5xdWVyeVNlbGVjdG9yQWxsKCdpbnB1dFt0eXBlPVwiaGlkZGVuXCJdJyk7XG4gICAgICAgICAgYmxvY2tQdWJsaXNoZWRJbnB1dHMuZm9yRWFjaChmdW5jdGlvbiAoZmllbGQpIHtcbiAgICAgICAgICAgIGlmIChmaWVsZC5pZC5pbmNsdWRlcyhcIl9ibG9ja19wdWJsaXNoZWRcIikpIHtcbiAgICAgICAgICAgICAgZmllbGQudmFsdWUgPSBldmVudC50YXJnZXQuY2hlY2tlZCA/ICcxJyA6ICcwJztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9KTtcbiAgICAgICAgICBpZiAoZXZlbnQudGFyZ2V0LmNoZWNrZWQpIHtcbiAgICAgICAgICAgIGJsb2NrLmNsYXNzTGlzdC5yZW1vdmUoJ2JvcmRlci1ncmF5Jyk7XG4gICAgICAgICAgICBibG9jay5jbGFzc0xpc3QuYWRkKCdib3JkZXItdGVhbCcpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBibG9jay5jbGFzc0xpc3QuYWRkKCdib3JkZXItZ3JheScpO1xuICAgICAgICAgICAgYmxvY2suY2xhc3NMaXN0LnJlbW92ZSgnYm9yZGVyLXRlYWwnKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICB9O1xuXG4gIC8vIGFjdGlvbiB3aGVuIHJlbW92ZSBhIGNvbnRlbnRcbiAgc2VsZi5oYW5kbGVSZW1vdmVDb250ZW50ID0gZnVuY3Rpb24gKGJsb2NrKSB7XG4gICAgYmxvY2sucXVlcnlTZWxlY3RvcignW2RhdGEtYWN0aW9uPVwiZGVsZXRlXCJdJylcbiAgICAgICAgLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIGlmIChjb25maXJtKHNlbGYud0ZsZXhpYmxlQmxvY2sucXVlcnlTZWxlY3RvcignI2NvbmZpcm1fc2VudGVuY2UnKS50ZXh0Q29udGVudCkpIHtcbiAgICAgICAgICAgIGNvbnN0IGNvbGxlY3Rpb24gPSBibG9jay5jbG9zZXN0KCdbZGF0YS1zeWxpdXMtZmxleGlibGUtY29udGVudC1maWVsZF0nKTtcbiAgICAgICAgICAgIGJsb2NrLnJlbW92ZSgpO1xuICAgICAgICAgICAgY29sbGVjdGlvbi5kYXRhc2V0Lm51bUl0ZW1zID0gY29sbGVjdGlvbi5xdWVyeVNlbGVjdG9yQWxsKCcuYmxvYy13cmFwcGVyJykubGVuZ3RoIC0gMTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICB9O1xuXG4gIC8vIGFjdGlvbiB3aGVuIGNsaWNrIHRvIHRvZ2dsZSBhIGJsb2NrXG4gIHNlbGYuaGFuZGxlVG9nZ2xlQ29udGVudCA9IGZ1bmN0aW9uIChibG9jaykge1xuICAgIGJsb2NrLnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWFjdGlvbj1cInRvZ2dsZVwiXScpXG4gICAgICAgIC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICAgICAgY29uc3QgaXNEb3duID0gZXZlbnQudGFyZ2V0LmNsYXNzTGlzdC5jb250YWlucygnZG93bicpO1xuICAgICAgICAgICAgYmxvY2sucXVlcnlTZWxlY3RvcignW2RhdGEtbGF5ZXI9XCJjb250ZW50XCJdJykuc3R5bGUuZGlzcGxheSA9IGlzRG93biA/ICdub25lJyA6ICdibG9jayc7XG4gICAgICAgICAgICBldmVudC50YXJnZXQuY2xhc3NMaXN0LnRvZ2dsZSgnZG93bicpXG4gICAgICAgIH0pO1xuICB9O1xuXG4gIHNlbGYuZ2V0QmxvY2tXcmFwcGVyUHJvdG90eXBlID0gZnVuY3Rpb24gKCkge1xuICAgIHJldHVybiBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgndy13cmFwcGVyLXByb3RvdHlwZScpXG4gICAgICAgIC5xdWVyeVNlbGVjdG9yKCcuYmxvYy13cmFwcGVyJyk7XG4gIH07XG5cbiAgc2VsZi5nZW5lcmF0ZU5ld0Jsb2NrID0gZnVuY3Rpb24gKGJsb2NrV3JhcHBlciwgZGF0YSwgaW5kZXgpIHtcbiAgICBjb25zdCB7IGZvcm1UeXBlTmFtZVBsYWNlaG9sZGVyLCBwcm90b3R5cGUgfSA9IGRhdGE7XG4gICAgY29uc3QgbGFiZWxSZWdleHAgPSBuZXcgUmVnRXhwKGAke2Zvcm1UeXBlTmFtZVBsYWNlaG9sZGVyfWxhYmVsX19gLCAnZycpO1xuICAgIGNvbnN0IG5hbWVSZWdleHAgPSBuZXcgUmVnRXhwKGZvcm1UeXBlTmFtZVBsYWNlaG9sZGVyLCAnZycpO1xuXG4gICAgY29uc3QgbmV3UHJvdG90eXBlID0gcHJvdG90eXBlXG4gICAgICAgIC5yZXBsYWNlKGxhYmVsUmVnZXhwLCBpbmRleClcbiAgICAgICAgLnJlcGxhY2UobmFtZVJlZ2V4cCwgaW5kZXgpO1xuXG4gICAgY29uc3QgYmxvY2tFbGVtZW50ID0gYmxvY2tXcmFwcGVyLmNsb25lTm9kZSh0cnVlKTtcblxuICAgIGNvbnN0IGNoZWNrYm94SW5OZXdCbG9jayA9IGJsb2NrRWxlbWVudC5xdWVyeVNlbGVjdG9yKCcuZm9ybS1jaGVjay1pbnB1dCcpO1xuICAgIGNvbnN0IGxhYmVsSW5OZXdCbG9jayA9IGJsb2NrRWxlbWVudC5xdWVyeVNlbGVjdG9yKCcuZm9ybS1jaGVjay1sYWJlbCcpO1xuICAgIGNvbnN0IGxhc3RCbG9ja0luQ29udGVudCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCd3LXdyYXBwZXItcHJvdG90eXBlJykucGFyZW50RWxlbWVudC5wYXJlbnRFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJy53LWZsZXhpYmxlLWNvbnRlbnQgPiAuY2FyZCcpO1xuICAgIGlmIChsYXN0QmxvY2tJbkNvbnRlbnQgJiYgY2hlY2tib3hJbk5ld0Jsb2NrICYmIGxhYmVsSW5OZXdCbG9jaykge1xuICAgICAgICBjb25zdCBpZEF0dHIgPSBsYXN0QmxvY2tJbkNvbnRlbnQucXVlcnlTZWxlY3RvcignLmZvcm0tY2hlY2staW5wdXQnKS5nZXRBdHRyaWJ1dGUoJ2lkJyk7XG4gICAgICAgIC8vIGdldCB0aGUgbnVtYmVyIHBhcnQgb2YgdGhlIGZvciBhdHRyaWJ1dGUgKGJsb2NrLWNoZWNrYm94LTMgLT4gMylcbiAgICAgICAgY29uc3QgbmV3SWQgPSBwYXJzZUludChpZEF0dHIuc3BsaXQoJy0nKVsyXSwgMTApICsgMTtcbiAgICAgICAgY2hlY2tib3hJbk5ld0Jsb2NrLnNldEF0dHJpYnV0ZSgnaWQnLCBgYmxvY2stY2hlY2tib3gtJHtuZXdJZH1gKTtcbiAgICAgICAgbGFiZWxJbk5ld0Jsb2NrLnNldEF0dHJpYnV0ZSgnZm9yJywgYGJsb2NrLWNoZWNrYm94LSR7bmV3SWR9YCk7XG4gICAgfVxuXG4gICAgY29uc3QgbW92ZUhlcmVXcmFwcGVyID0gYmxvY2tXcmFwcGVyLm5leHRFbGVtZW50U2libGluZy5jbG9uZU5vZGUodHJ1ZSk7XG4gICAgYmxvY2tFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWxheWVyPVwiY29udGVudFwiXScpXG4gICAgICAgIC5pbnNlcnRBZGphY2VudEhUTUwoJ2JlZm9yZWVuZCcsIG5ld1Byb3RvdHlwZSk7XG5cbiAgICByZXR1cm4gW2Jsb2NrRWxlbWVudCwgbW92ZUhlcmVXcmFwcGVyXTtcbiAgfTtcblxuICBzZWxmLmFwcGVuZE5ld0Jsb2NrID0gZnVuY3Rpb24gKGNvbnRlbnQpIHtcblxuICAgIGNvbnN0IHJlbW90ZSA9IFtdO1xuXG4gICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ3ctd3JhcHBlci1wcm90b3R5cGUnKS5iZWZvcmUoY29udGVudCk7XG5cbiAgICBBcnJheS5mcm9tKHNlbGYud0ZsZXhpYmxlQ29udGVudC5sYXN0RWxlbWVudENoaWxkLnByZXZpb3VzRWxlbWVudFNpYmxpbmcucXVlcnlTZWxlY3RvckFsbCgnc2NyaXB0JykpXG4gICAgICAgIC5mb3JFYWNoKChvbGRTY3JpcHQpID0+IHtcbiAgICAgICAgICBpZiAob2xkU2NyaXB0LnNyYykge1xuICAgICAgICAgICAgcmVtb3RlLnB1c2goc2VsZi5sb2FkU2NyaXB0KG9sZFNjcmlwdC5zcmMpKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgQXJyYXkuZnJvbShzZWxmLndGbGV4aWJsZUNvbnRlbnQubGFzdEVsZW1lbnRDaGlsZC5wcmV2aW91c0VsZW1lbnRTaWJsaW5nLnF1ZXJ5U2VsZWN0b3JBbGwoJ2xpbmsnKSlcbiAgICAgICAgLmZvckVhY2goKG9sZFNjcmlwdCkgPT4ge1xuICAgICAgICAgIGlmIChvbGRTY3JpcHQuaHJlZiAmJiBvbGRTY3JpcHQucmVsID09PSAnc3R5bGVzaGVldCcpIHtcbiAgICAgICAgICAgIHJlbW90ZS5wdXNoKHNlbGYubG9hZFN0eWxlc2hlZXQob2xkU2NyaXB0LmhyZWYpKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgcmV0dXJuIG5ldyBQcm9taXNlKChyZXNvbHZlKSA9PiB7XG4gICAgICBQcm9taXNlLmFsbChyZW1vdGUpXG4gICAgICAgICAgLnRoZW4oKCkgPT4ge1xuICAgICAgICAgICAgc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICAgICAgIEFycmF5LmZyb20oc2VsZi53RmxleGlibGVDb250ZW50Lmxhc3RFbGVtZW50Q2hpbGQucHJldmlvdXNFbGVtZW50U2libGluZy5xdWVyeVNlbGVjdG9yQWxsKCdzY3JpcHQnKSlcbiAgICAgICAgICAgICAgICAgIC5mb3JFYWNoKChvbGRTY3JpcHQpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCFvbGRTY3JpcHQuc3JjKSB7XG4gICAgICAgICAgICAgICAgICAgICAgY29uc3QgbmV3U2NyaXB0ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnc2NyaXB0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgQXJyYXkuZnJvbShvbGRTY3JpcHQuYXR0cmlidXRlcylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgLmZvckVhY2goKGF0dHIpID0+IG5ld1NjcmlwdC5zZXRBdHRyaWJ1dGUoYXR0ci5uYW1lLCBhdHRyLnZhbHVlKSk7XG4gICAgICAgICAgICAgICAgICAgICAgbmV3U2NyaXB0LmFwcGVuZENoaWxkKGRvY3VtZW50LmNyZWF0ZVRleHROb2RlKG9sZFNjcmlwdC5pbm5lckhUTUwpKTtcbiAgICAgICAgICAgICAgICAgICAgICBvbGRTY3JpcHQucGFyZW50Tm9kZS5yZXBsYWNlQ2hpbGQobmV3U2NyaXB0LCBvbGRTY3JpcHQpO1xuICAgICAgICAgICAgICAgICAgICAgIHNlbGYuZXZhbFNjcmlwdChvbGRTY3JpcHQuaW5uZXJIVE1MKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgIHJlc29sdmUoKTtcbiAgICAgICAgICAgIH0sIDEpO1xuICAgICAgICAgIH0pO1xuICAgIH0pO1xuICB9O1xuXG4gIHNlbGYuZXZhbFNjcmlwdCA9IGZ1bmN0aW9uKGNvbnRlbnQpIHtcbiAgICByZXR1cm4gbmV3IFByb21pc2UoKHJlc29sdmUpID0+IHtcbiAgICAgIGV2YWwoY29udGVudCk7XG4gICAgICByZXNvbHZlKCk7XG4gICAgfSk7XG4gIH07XG5cbiAgc2VsZi5sb2FkU2NyaXB0ID0gZnVuY3Rpb24oc3JjKSB7XG4gICAgcmV0dXJuIG5ldyBQcm9taXNlKChyZXNvbHZlLCByZWplY3QpID0+IHtcbiAgICAgIGNvbnN0IHNjcmlwdCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3NjcmlwdCcpO1xuICAgICAgc2NyaXB0LnNyYyA9IHNyYztcbiAgICAgIHNjcmlwdC50eXBlID0gJ3RleHQvamF2YXNjcmlwdCc7XG5cbiAgICAgIHNjcmlwdC5vbmxvYWQgPSAoKSA9PiByZXNvbHZlKHNjcmlwdCk7XG4gICAgICBzY3JpcHQub25lcnJvciA9ICgpID0+IHJlamVjdChuZXcgRXJyb3IoYFN0eWxlIGxvYWQgZXJyb3IgZm9yICR7c3JjfWApKTtcblxuICAgICAgZG9jdW1lbnQuaGVhZC5hcHBlbmQoc2NyaXB0KTtcbiAgICAgIHJlc29sdmUoKTtcbiAgICB9KTtcbiAgfTtcblxuICBzZWxmLmxvYWRTdHlsZXNoZWV0ID0gZnVuY3Rpb24oc3JjKSB7XG4gICAgcmV0dXJuIG5ldyBQcm9taXNlKChyZXNvbHZlLCByZWplY3QpID0+IHtcbiAgICAgIGNvbnN0IGxpbmsgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdsaW5rJyk7XG4gICAgICBsaW5rLmhyZWYgPSBzcmM7XG4gICAgICBsaW5rLnJlbCA9ICdzdHlsZXNoZWV0JztcblxuICAgICAgbGluay5vbmxvYWQgPSAoKSA9PiByZXNvbHZlKGxpbmspO1xuICAgICAgbGluay5vbmVycm9yID0gKCkgPT4gcmVqZWN0KG5ldyBFcnJvcihgU3R5bGUgbG9hZCBlcnJvciBmb3IgJHtzcmN9YCkpO1xuXG4gICAgICBkb2N1bWVudC5oZWFkLmFwcGVuZChsaW5rKTtcbiAgICAgIHJlc29sdmUoKTtcbiAgICB9KTtcbiAgfTtcblxuICBzZWxmLmhhbmRsZUFkZEJ1dHRvbiA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgIGV2ZW50LnN0b3BQcm9wYWdhdGlvbigpO1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgY29uc3QgYWRkQnV0dG9uID0gZXZlbnQudGFyZ2V0O1xuICAgIGNvbnN0IGNvbGxlY3Rpb24gPSBhZGRCdXR0b24uY2xvc2VzdCgnLmZpZWxkLWNvbGxlY3Rpb24nKTtcbiAgICBsZXQgbnVtSXRlbXMgPSBwYXJzZUludChjb2xsZWN0aW9uLmRhdGFzZXQubnVtSXRlbXMpO1xuICAgIGNvbGxlY3Rpb24uZGF0YXNldC5udW1JdGVtcyA9ICsrbnVtSXRlbXM7XG4gICAgc2VsZi5hZGROZXdCbG9jayhhZGRCdXR0b24uZGF0YXNldCwgc2VsZi5nZW5lcmF0ZUl0ZW1JZCgpKTtcbiAgICBpZiAoZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmVtcHR5LWZsZXhpYmxlLWNvbnRlbnQnKSkge1xuICAgICAgZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmVtcHR5LWZsZXhpYmxlLWNvbnRlbnQnKS5yZW1vdmUoKTtcbiAgICB9XG5cbiAgICBjb25zdCBteU1vZGFsRWwgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjYmxvY2stbGlzdCcpXG4gICAgY29uc3QgbW9kYWwgPSBib290c3RyYXAuTW9kYWwuZ2V0T3JDcmVhdGVJbnN0YW5jZShteU1vZGFsRWwpO1xuICAgIG1vZGFsLmhpZGUoKTtcbiAgICByZXR1cm4gZmFsc2U7XG4gIH07XG5cbiAgc2VsZi5nZW5lcmF0ZUl0ZW1JZCA9IGZ1bmN0aW9uICgpIHtcbiAgICBsZXQgZHQgPSBuZXcgRGF0ZSgpLmdldFRpbWUoKTtcbiAgICBjb25zdCB1dWlkID0gJ3h4eHgteHh4Jy5yZXBsYWNlKC9beHldL2csIChjKSA9PiB7XG4gICAgICBjb25zdCByID0gKGR0ICsgTWF0aC5yYW5kb20oKSoxNiklMTYgfCAwO1xuICAgICAgZHQgPSBNYXRoLmZsb29yKGR0LzE2KTtcbiAgICAgIHJldHVybiAoYz09J3gnID8gciA6KHImMHgzfDB4OCkpLnRvU3RyaW5nKDE2KTtcbiAgICB9KTtcbiAgICByZXR1cm4gdXVpZDtcbiAgfTtcblxuICBzZWxmLmxpc3RlbkJsb2NrQ2F0ZWdvcmllc0NoYW5nZXMgPSBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICBjb25zdCBhY3RpdmVDYXRlZ29yeSA9IGV2ZW50LnRhcmdldC52YWx1ZTtcbiAgICBldmVudC50YXJnZXQuY2xvc2VzdCgnLnctZmxleGlibGUtYmxvY2tzJylcbiAgICAgICAgLnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRhLWJsb2NrLWNhdGVnb3J5XScpXG4gICAgICAgIC5mb3JFYWNoKChjYXJkKSA9PiB7XG4gICAgICAgICAgY2FyZC5zdHlsZS5kaXNwbGF5ID0gYWN0aXZlQ2F0ZWdvcnkgPT09IG51bGwgfHwgYWN0aXZlQ2F0ZWdvcnkgPT09ICdhbGxfYmxvY2tzJyA/ICdibG9jaycgOiAnbm9uZSc7XG4gICAgICAgIH0pO1xuICAgIGlmIChhY3RpdmVDYXRlZ29yeSkge1xuICAgICAgZXZlbnQudGFyZ2V0LmNsb3Nlc3QoJy53LWZsZXhpYmxlLWJsb2NrcycpXG4gICAgICAgICAgLnF1ZXJ5U2VsZWN0b3JBbGwoYFtkYXRhLWJsb2NrLWNhdGVnb3J5PVwiJHthY3RpdmVDYXRlZ29yeX1cIl1gKVxuICAgICAgICAgIC5mb3JFYWNoKChjYXJkKSA9PiB7XG4gICAgICAgICAgICBjYXJkLnN0eWxlLmRpc3BsYXkgPSAnYmxvY2snO1xuICAgICAgICAgIH0pO1xuICAgIH1cbiAgfTtcblxuICBzZWxmLmxpc3RlbkJsb2NrRmlsdGVyQ2hhbmdlcyA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgIGNvbnN0IGZpbHRlciA9IGV2ZW50LnRhcmdldC52YWx1ZTtcbiAgICAgIGV2ZW50LnRhcmdldC5jbG9zZXN0KCcudy1mbGV4aWJsZS1ibG9ja3MnKVxuICAgICAgICAgIC5xdWVyeVNlbGVjdG9yQWxsKGBbZGF0YS1ibG9jay1jYXRlZ29yeV0gLmNhcmQtdGl0bGVgKVxuICAgICAgICAgIC5mb3JFYWNoKCh0aXRsZSkgPT4ge1xuICAgICAgICAgICAgICBjb25zdCByZWdleCA9IG5ldyBSZWdFeHAoZmlsdGVyLCAnaScpO1xuICAgICAgICAgICAgICB0aXRsZS5wYXJlbnRFbGVtZW50LnBhcmVudEVsZW1lbnQuc3R5bGUuZGlzcGxheSA9IHJlZ2V4LnRlc3QodGl0bGUuaW5uZXJUZXh0KSB8fCAhZmlsdGVyID8gJ2Jsb2NrJyA6ICdub25lJztcbiAgICAgICAgICB9KTtcbiAgfTtcblxuICBpbml0TW9kdWxlKCk7XG4gIGluaXRJZnJhbWVQcmV2aWV3TW9kdWxlKCk7XG59O1xuXG53aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcignRE9NQ29udGVudExvYWRlZCcsIGZsZXhpYmxlQ29udGVudE1vZHVsZSk7XG4iXSwibmFtZXMiOlsiZSIsInQiLCJyIiwiU3ltYm9sIiwibiIsIml0ZXJhdG9yIiwibyIsInRvU3RyaW5nVGFnIiwiaSIsImMiLCJwcm90b3R5cGUiLCJHZW5lcmF0b3IiLCJ1IiwiT2JqZWN0IiwiY3JlYXRlIiwiX3JlZ2VuZXJhdG9yRGVmaW5lMiIsImYiLCJwIiwieSIsIkciLCJ2IiwiYSIsImQiLCJiaW5kIiwibGVuZ3RoIiwibCIsIlR5cGVFcnJvciIsImNhbGwiLCJkb25lIiwidmFsdWUiLCJHZW5lcmF0b3JGdW5jdGlvbiIsIkdlbmVyYXRvckZ1bmN0aW9uUHJvdG90eXBlIiwiZ2V0UHJvdG90eXBlT2YiLCJzZXRQcm90b3R5cGVPZiIsIl9fcHJvdG9fXyIsImRpc3BsYXlOYW1lIiwiX3JlZ2VuZXJhdG9yIiwidyIsIm0iLCJkZWZpbmVQcm9wZXJ0eSIsIl9yZWdlbmVyYXRvckRlZmluZSIsIl9pbnZva2UiLCJlbnVtZXJhYmxlIiwiY29uZmlndXJhYmxlIiwid3JpdGFibGUiLCJhc3luY0dlbmVyYXRvclN0ZXAiLCJQcm9taXNlIiwicmVzb2x2ZSIsInRoZW4iLCJfYXN5bmNUb0dlbmVyYXRvciIsImFyZ3VtZW50cyIsImFwcGx5IiwiX25leHQiLCJfdGhyb3ciLCJmbGV4aWJsZUNvbnRlbnRNb2R1bGUiLCJzZWxmIiwiYmxvY2tUb01vdmUiLCJpbml0SWZyYW1lUHJldmlld01vZHVsZSIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvckFsbCIsImZvckVhY2giLCJsaW5rIiwidG9vbHRpcEluc3RhbmNlIiwiYWRkRXZlbnRMaXN0ZW5lciIsIl9jYWxsZWUiLCJ1cmwiLCJ2dyIsInZoIiwid2lkdGgiLCJoZWlnaHQiLCJ0b29sdGlwIiwiX2NvbnRleHQiLCJkYXRhc2V0IiwiTWF0aCIsIm1heCIsImRvY3VtZW50RWxlbWVudCIsImNsaWVudFdpZHRoIiwid2luZG93IiwiaW5uZXJXaWR0aCIsImNsaWVudEhlaWdodCIsImlubmVySGVpZ2h0IiwibWluIiwiYm9vdHN0cmFwIiwiVG9vbHRpcCIsImh0bWwiLCJ0ZW1wbGF0ZSIsImNvbmNhdCIsInBsYWNlbWVudCIsInRyaWdnZXIiLCJjb250YWluZXIiLCJzaG93Iiwic2V0VGltZW91dCIsInF1ZXJ5U2VsZWN0b3IiLCJpZnJhbWUiLCJjcmVhdGVFbGVtZW50Iiwic3JjIiwic3R5bGUiLCJvbmxvYWQiLCJsb2FkZXIiLCJyZW1vdmUiLCJvcGFjaXR5IiwiYXBwZW5kQ2hpbGQiLCJnZXRJbnN0YW5jZSIsImhpZGUiLCJpbml0TW9kdWxlIiwid0ZsZXhpYmxlQ29udGVudCIsIndGbGV4aWJsZUJsb2NrIiwiZHJvcERvd25CbG9ja0NhdGVnb3JpZXMiLCJsaXN0ZW5CbG9ja0NhdGVnb3JpZXNDaGFuZ2VzIiwiZmlsdGVyQmxvY2tzIiwibGlzdGVuQmxvY2tGaWx0ZXJDaGFuZ2VzIiwiZ2V0RWxlbWVudEJ5SWQiLCJmb2N1cyIsImFkZEJ1dHRvbiIsImhhbmRsZUFkZEJ1dHRvbiIsImJsb2NrIiwiaGFuZGxlQmxvY2siLCJlbCIsImhhbmRsZUNsaWNrTW92ZSIsInJlY2FsY3VsYXRlQmxvY2tQb3NpdGlvbnMiLCJldiIsIm5leHRNb3ZlTGF5ZXIiLCJuZXh0RWxlbWVudFNpYmxpbmciLCJhZnRlciIsImV2ZW50IiwiRXZlbnQiLCJkaXNwYXRjaEV2ZW50IiwiY29sbGVjdGlvbiIsImNsb3Nlc3QiLCJibG9ja1Bvc2l0aW9uSW5wdXRzIiwiY291bnQiLCJmaWVsZCIsImlkIiwiaW5jbHVkZXMiLCJoYW5kbGVUb2dnbGVDb250ZW50IiwiaGFuZGxlTW92ZUNvbnRlbnQiLCJoYW5kbGVSZW1vdmVDb250ZW50IiwiaGFuZGxlUHVibGlzaENvbnRlbnQiLCJhZGROZXdCbG9jayIsImRhdGEiLCJpbmRleCIsImJsb2NrV3JhcHBlciIsImdldEJsb2NrV3JhcHBlclByb3RvdHlwZSIsIm5ld0NvbnRlbnRzIiwiZ2VuZXJhdGVOZXdCbG9jayIsImFwcGVuZE5ld0Jsb2NrIiwicHJldmlvdXNFbGVtZW50U2libGluZyIsImJlZm9yZSIsImJsb2NrTmFtZSIsImlubmVySFRNTCIsIkFycmF5IiwiZnJvbSIsImxhc3RFbGVtZW50Q2hpbGQiLCJvbGRTY3JpcHQiLCJldmFsU2NyaXB0IiwiaGlkZU1vdmVIZXJlIiwibW92ZUJ1dHRvbiIsIm1vdmVFbmFibGVkIiwiZ2V0QXR0cmlidXRlIiwiZGlzcGxheSIsImhhbmRsZUVzY2FwZUtleVByZXNzIiwia2V5IiwicmVtb3ZlQXR0cmlidXRlIiwiY2xhc3NMaXN0IiwicmVtb3ZlRXZlbnRMaXN0ZW5lciIsInRhcmdldCIsInNldEF0dHJpYnV0ZSIsImFkZCIsImJsb2NrUHVibGlzaGVkSW5wdXRzIiwiY2hlY2tlZCIsImNvbmZpcm0iLCJ0ZXh0Q29udGVudCIsIm51bUl0ZW1zIiwiaXNEb3duIiwiY29udGFpbnMiLCJ0b2dnbGUiLCJmb3JtVHlwZU5hbWVQbGFjZWhvbGRlciIsImxhYmVsUmVnZXhwIiwiUmVnRXhwIiwibmFtZVJlZ2V4cCIsIm5ld1Byb3RvdHlwZSIsInJlcGxhY2UiLCJibG9ja0VsZW1lbnQiLCJjbG9uZU5vZGUiLCJjaGVja2JveEluTmV3QmxvY2siLCJsYWJlbEluTmV3QmxvY2siLCJsYXN0QmxvY2tJbkNvbnRlbnQiLCJwYXJlbnRFbGVtZW50IiwiaWRBdHRyIiwibmV3SWQiLCJwYXJzZUludCIsInNwbGl0IiwibW92ZUhlcmVXcmFwcGVyIiwiaW5zZXJ0QWRqYWNlbnRIVE1MIiwiY29udGVudCIsInJlbW90ZSIsInB1c2giLCJsb2FkU2NyaXB0IiwiaHJlZiIsInJlbCIsImxvYWRTdHlsZXNoZWV0IiwiYWxsIiwibmV3U2NyaXB0IiwiYXR0cmlidXRlcyIsImF0dHIiLCJuYW1lIiwiY3JlYXRlVGV4dE5vZGUiLCJwYXJlbnROb2RlIiwicmVwbGFjZUNoaWxkIiwiZXZhbCIsInJlamVjdCIsInNjcmlwdCIsInR5cGUiLCJvbmVycm9yIiwiRXJyb3IiLCJoZWFkIiwiYXBwZW5kIiwic3RvcFByb3BhZ2F0aW9uIiwicHJldmVudERlZmF1bHQiLCJnZW5lcmF0ZUl0ZW1JZCIsIm15TW9kYWxFbCIsIm1vZGFsIiwiTW9kYWwiLCJnZXRPckNyZWF0ZUluc3RhbmNlIiwiZHQiLCJEYXRlIiwiZ2V0VGltZSIsInV1aWQiLCJyYW5kb20iLCJmbG9vciIsInRvU3RyaW5nIiwiYWN0aXZlQ2F0ZWdvcnkiLCJjYXJkIiwiZmlsdGVyIiwidGl0bGUiLCJyZWdleCIsInRlc3QiLCJpbm5lclRleHQiXSwic291cmNlUm9vdCI6IiJ9