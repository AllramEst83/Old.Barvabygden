// Polyfills for older browser support
(function () {
  "use strict";

  // Polyfill for fetch API (for IE and very old browsers)
  if (!window.fetch) {
    // Simple XMLHttpRequest wrapper for basic fetch functionality
    window.fetch = function (url, options) {
      return new Promise(function (resolve, reject) {
        var xhr = new XMLHttpRequest();
        var method = (options && options.method) || "GET";

        xhr.open(method, url);

        if (options && options.headers) {
          for (var key in options.headers) {
            xhr.setRequestHeader(key, options.headers[key]);
          }
        }

        xhr.onload = function () {
          resolve({
            ok: xhr.status >= 200 && xhr.status < 300,
            status: xhr.status,
            statusText: xhr.statusText,
            text: function () {
              return Promise.resolve(xhr.responseText);
            },
            json: function () {
              return Promise.resolve(JSON.parse(xhr.responseText));
            },
          });
        };

        xhr.onerror = function () {
          reject(new TypeError("Network request failed"));
        };

        xhr.send((options && options.body) || null);
      });
    };
  }

  // Polyfill for Promise (for IE)
  if (!window.Promise) {
    // Simple Promise polyfill
    window.Promise = function (executor) {
      var self = this;
      self.state = "pending";
      self.value = undefined;
      self.handlers = [];

      function resolve(result) {
        if (self.state === "pending") {
          self.state = "fulfilled";
          self.value = result;
          self.handlers.forEach(handle);
          self.handlers = null;
        }
      }

      function reject(error) {
        if (self.state === "pending") {
          self.state = "rejected";
          self.value = error;
          self.handlers.forEach(handle);
          self.handlers = null;
        }
      }

      function handle(handler) {
        if (self.state === "pending") {
          self.handlers.push(handler);
        } else {
          if (self.state === "fulfilled" && handler.onFulfilled) {
            handler.onFulfilled(self.value);
          }
          if (self.state === "rejected" && handler.onRejected) {
            handler.onRejected(self.value);
          }
        }
      }

      this.then = function (onFulfilled, onRejected) {
        return new Promise(function (resolve, reject) {
          handle({
            onFulfilled: function (result) {
              try {
                if (typeof onFulfilled === "function") {
                  resolve(onFulfilled(result));
                } else {
                  resolve(result);
                }
              } catch (ex) {
                reject(ex);
              }
            },
            onRejected: function (error) {
              try {
                if (typeof onRejected === "function") {
                  resolve(onRejected(error));
                } else {
                  reject(error);
                }
              } catch (ex) {
                reject(ex);
              }
            },
          });
        });
      };

      // Add finally method to Promise prototype
      this.finally = function (onFinally) {
        return this.then(
          function (value) {
            return Promise.resolve(onFinally()).then(function () {
              return value;
            });
          },
          function (reason) {
            return Promise.resolve(onFinally()).then(function () {
              throw reason;
            });
          }
        );
      };

      executor(resolve, reject);
    };

    Promise.resolve = function (value) {
      return new Promise(function (resolve) {
        resolve(value);
      });
    };

    Promise.reject = function (reason) {
      return new Promise(function (resolve, reject) {
        reject(reason);
      });
    };

    // Polyfill for Promise.allSettled (used in site.js)
    Promise.allSettled = function (promises) {
      return new Promise(function (resolve) {
        var results = [];
        var remaining = promises.length;

        if (remaining === 0) {
          resolve(results);
          return;
        }

        promises.forEach(function (promise, index) {
          Promise.resolve(promise)
            .then(function (value) {
              results[index] = { status: "fulfilled", value: value };
            })
            .catch(function (reason) {
              results[index] = { status: "rejected", reason: reason };
            })
            .finally(function () {
              remaining--;
              if (remaining === 0) {
                resolve(results);
              }
            });
        });
      });
    };
  }

  // Polyfill for Array.from (for IE)
  if (!Array.from) {
    Array.from = function (arrayLike) {
      var result = [];
      for (var i = 0; i < arrayLike.length; i++) {
        result.push(arrayLike[i]);
      }
      return result;
    };
  }

  // Polyfill for Object.assign (for IE)
  if (!Object.assign) {
    Object.assign = function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];
        for (var key in source) {
          if (source.hasOwnProperty(key)) {
            target[key] = source[key];
          }
        }
      }
      return target;
    };
  }

  // Polyfill for addEventListener for very old browsers
  if (!window.addEventListener) {
    window.addEventListener = function (type, listener) {
      window.attachEvent("on" + type, listener);
    };
  }

  // Polyfill for classList (for IE9 and below)
  if (!("classList" in document.createElement("_"))) {
    (function (view) {
      if (!("Element" in view)) return;

      var classListProp = "classList",
        protoProp = "prototype",
        elemCtrProto = view.Element[protoProp],
        objCtr = Object,
        strTrim =
          String[protoProp].trim ||
          function () {
            return this.replace(/^\s+|\s+$/g, "");
          },
        arrIndexOf =
          Array[protoProp].indexOf ||
          function (item) {
            var i = 0,
              len = this.length;
            for (; i < len; i++) {
              if (i in this && this[i] === item) {
                return i;
              }
            }
            return -1;
          },
        DOMTokenList = function (el) {
          this.el = el;
          var classes = el.className.replace(/^\s+|\s+$/g, "").split(/\s+/);
          for (var i = 0; i < classes.length; i++) {
            this.push(classes[i]);
          }
          this._updateClassName = function () {
            el.className = this.toString();
          };
        },
        tokenListProto = (DOMTokenList[protoProp] = []),
        tokenListGetter = function () {
          return new DOMTokenList(this);
        };

      tokenListProto.item = function (i) {
        return this[i] || null;
      };
      tokenListProto.contains = function (token) {
        token += "";
        return arrIndexOf.call(this, token) !== -1;
      };
      tokenListProto.add = function () {
        var tokens = arguments,
          i = 0,
          l = tokens.length,
          token,
          updated = false;
        do {
          token = tokens[i] + "";
          if (arrIndexOf.call(this, token) === -1) {
            this.push(token);
            updated = true;
          }
        } while (++i < l);

        if (updated) {
          this._updateClassName();
        }
      };
      tokenListProto.remove = function () {
        var tokens = arguments,
          i = 0,
          l = tokens.length,
          token,
          updated = false,
          index;
        do {
          token = tokens[i] + "";
          index = arrIndexOf.call(this, token);
          while (index !== -1) {
            this.splice(index, 1);
            updated = true;
            index = arrIndexOf.call(this, token);
          }
        } while (++i < l);

        if (updated) {
          this._updateClassName();
        }
      };
      tokenListProto.toggle = function (token, force) {
        token += "";

        var result = this.contains(token),
          method = result
            ? force !== true && "remove"
            : force !== false && "add";

        if (method) {
          this[method](token);
        }

        if (force === true || force === false) {
          return force;
        } else {
          return !result;
        }
      };
      tokenListProto.toString = function () {
        return this.join(" ");
      };

      if (objCtr.defineProperty) {
        var defineProperty = {
          get: tokenListGetter,
          enumerable: true,
          configurable: true,
        };
        try {
          objCtr.defineProperty(elemCtrProto, classListProp, defineProperty);
        } catch (ex) {
          // IE 8 doesn't support enumerable:true
          defineProperty.enumerable = false;
          objCtr.defineProperty(elemCtrProto, classListProp, defineProperty);
        }
      } else if (objCtr[protoProp].__defineGetter__) {
        elemCtrProto.__defineGetter__(classListProp, tokenListGetter);
      }
    })(window);
  }

  // Polyfill for CustomEvent (for IE)
  if (typeof window.CustomEvent !== "function") {
    function CustomEvent(event, params) {
      params = params || {
        bubbles: false,
        cancelable: false,
        detail: undefined,
      };
      var evt = document.createEvent("CustomEvent");
      evt.initCustomEvent(
        event,
        params.bubbles,
        params.cancelable,
        params.detail
      );
      return evt;
    }
    CustomEvent.prototype = window.Event.prototype;
    window.CustomEvent = CustomEvent;
  }

  // CSS Custom Properties fallback detection and warning
  function supportsCSSVariables() {
    var div = document.createElement("div");
    div.style.setProperty("--test", "test");
    return div.style.getPropertyValue("--test") === "test";
  }

  // Add CSS fallback class if custom properties aren't supported
  if (!supportsCSSVariables()) {
    document.documentElement.className += " no-css-variables";
    console.warn(
      "CSS custom properties not supported. Consider adding fallback styles."
    );
  }

  // Smooth scroll polyfill for older browsers
  if (!("scrollBehavior" in document.documentElement.style)) {
    // Simple smooth scroll implementation
    window.smoothScrollTo = function (targetY, duration) {
      duration = duration || 600;
      var startY = window.pageYOffset;
      var difference = targetY - startY;
      var startTime = performance.now();

      function step() {
        var progress = Math.min((performance.now() - startTime) / duration, 1);
        var ease = 0.5 * (1 - Math.cos(progress * Math.PI));
        window.scrollTo(0, startY + difference * ease);
        if (progress < 1) {
          requestAnimationFrame(step);
        }
      }
      requestAnimationFrame(step);
    };
  }
})();
