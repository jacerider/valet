declare var autoComplete:any;
declare var drupalSettings:any;

(function () {

  class Valet {
    public $element: HTMLElement;
    public $input: HTMLInputElement;
    public $close: HTMLElement;
    public isOpen: boolean = false;
    public autoCompleteJS: any;
    public keysPressed: Object = {};

    /**
     * Construct.
     */
    constructor() {
      this.$element = document.getElementById('valet');
      this.$input = document.getElementById('valet--input') as HTMLInputElement;
      this.$close = document.getElementById('valet--close');

      this.bind();
      this.build();
    }

    protected bind() {
      // On keydown.
      document.addEventListener('keydown', (event) => {
        this.keysPressed[event.key] = true;
        if (this.keysPressed['Shift'] && this.keysPressed[' ']) {
          if (!this.isEditableElement(document.activeElement)) {
            event.preventDefault();
            this.open();
          }
        }
        if (this.isOpen === true && this.keysPressed['Escape']) {
          event.preventDefault();
          this.close();
        }
      });
      // On keyup.
      document.addEventListener('keyup', (event) => {
        delete this.keysPressed[event.key];
      });
      // On valet click.
      this.$element.addEventListener('click', e => {
        e.preventDefault();
        if (e.target === this.$element) {
          this.close();
        }
      });
      // On close button click.
      this.$close.addEventListener('click', e => {
        e.preventDefault();
        this.close();
      });
    }

    protected build() {
      this.autoCompleteJS = new autoComplete({
        selector: '#valet--input',
        data: {
          src: this.getData,
          cache: true,
          filter: (list) => {
            const history = this.getSelectionHistory();
            return list.filter((value, index, self) => {
              return self.findIndex(v => v.value.url === value.value.url) === index;
            }).sort((a, b) => {
              // Prioritize label matches over tag-only matches.
              const aLabel = a.key === 'label' ? 1 : 0;
              const bLabel = b.key === 'label' ? 1 : 0;
              if (aLabel !== bLabel) return bLabel - aLabel;
              // Then sort by selection history.
              const aCount = history[a.value.url] || 0;
              const bCount = history[b.value.url] || 0;
              return bCount - aCount;
            });
          },
          keys: ['label', 'tags'],
        },
        threshold: 1,
        debounce: 50,
        resultsList: {
          element: (list, data) => {
            const info = document.createElement('p');
            info.classList.add('overview');
            if (data.results.length > 0) {
              info.innerHTML = `Displaying <strong>${data.results.length}</strong> out of <strong>${data.matches.length}</strong> results`;
            } else {
              info.innerHTML = `Found <strong>${data.matches.length}</strong> matching results for <strong>"${data.query}"</strong>`;
            }
            list.append(info);
          },
          noResults: true,
          maxResults: 6,
          tabSelect: true
        },
        resultItem: {
          element: (item, data) => {
            if (item.id === 'autoComplete_result_0') {
              // Prevent style snapping.
              item.setAttribute('aria-selected', true);
            }
            // Modify Results Item Content
            item.innerHTML = `
            <span class="title">
              <span class="icon ${data.value.icon}"></span>
              ${data.value.label}
            </span>
            <span class="description">
              ${data.value.description}
            </span>
            <span class="url">
              ${data.value.url.split('?')[0]}
            </span>`;
          },
          highlight: true
        },
      });
      this.autoCompleteJS.input.addEventListener('results', this.onResults);
      this.autoCompleteJS.input.addEventListener('selection', this.onSelection);
    }

    public onResults = e => {
      setTimeout(() => {
        this.autoCompleteJS.goTo(0);
      });
    }

    public onSelection = e => {
      const feedback = e.detail;
      const selection = feedback.selection.value;
      if (typeof selection.url === 'string') {
        this.trackSelection(selection.url);
        this.go(selection.url);
      }
    }

    protected getSelectionHistory(): Record<string, number> {
      try {
        return JSON.parse(localStorage.getItem('valetHistory')) || {};
      } catch (e) {
        return {};
      }
    }

    protected trackSelection(url: string) {
      const history = this.getSelectionHistory();
      history[url] = (history[url] || 0) + 1;
      localStorage.setItem('valetHistory', JSON.stringify(history));
    }

    public go(value) {
      value = value.replace('RETURN_URL', window.location.pathname.substring(1));
      if (this.keysPressed['Meta']) {
        this.close();
        window.open(value);
      }
      else {
        this.$input.setAttribute('placeholder', 'Please wait...');
        this.$input.value = '';
        this.$input.disabled = true;
        window.location = value;
      }
    }

    public getData = async () => {
      try {
        const cacheId = this.$element.dataset.cacheId;
        let data = localStorage ? JSON.parse(localStorage.getItem('valetData')) : null;
        if (localStorage && data !== null) {
          const oldCacheId = localStorage.getItem('valetCacheId');
          if (cacheId !== oldCacheId) {
            localStorage.setItem('valetCacheId', cacheId);
            data = null;
          }
        }
        // Fetch new data.
        if (data === null) {
          const data = await this.fetchData().then(data => {
            const dataString = JSON.stringify(data);
            if (dataString !== JSON.stringify({})) {
              localStorage.setItem('valetData', dataString);
            }
            return data;
          });
          return data;
        }
        else {
          return data;
        }
      } catch (e) {
        return e;
      }
    }

    protected fetchData = async () => {
      try {
        const source = await fetch(
          drupalSettings.path.baseUrl + 'api/valet'
        );
        const data = await source.json();
        return data;
      } catch (e) {
        return e;
      }
    }

    public open() {
      this.keysPressed = {};
      this.isOpen = true;
      this.$element.classList.add('valet--active');
      this.$input.value = '';
      setTimeout(() => {
        this.$input.focus();
      }, 300);
    }

    public close() {
      this.isOpen = false;
      this.$element.classList.remove('valet--active');
    }

    protected isEditableElement = (el: EventTarget) => {
      if (el instanceof HTMLElement && el.isContentEditable) return true;
      if (el instanceof HTMLInputElement) {
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#input_types
        if (/|text|email|number|password|search|tel|url/.test(el.type || '')) {
          return !(el.disabled || el.readOnly);
        }
      }
      if (el instanceof HTMLTextAreaElement) return !(el.disabled || el.readOnly);
      return false;
    }

  }

  new Valet();

})();
