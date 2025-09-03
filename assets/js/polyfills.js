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
