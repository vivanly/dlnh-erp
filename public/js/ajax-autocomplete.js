(function () {
    let autocompleteIndex = 0;

    function normalizeElements(elements) {
        if (typeof elements === 'string') {
            return Array.from(document.querySelectorAll(elements));
        }

        if (elements && elements.jquery) {
            return elements.toArray();
        }

        if (elements instanceof Element) {
            return [elements];
        }

        return Array.from(elements || []);
    }

    function responseItems(response) {
        if (Array.isArray(response)) {
            return response;
        }

        if (response && Array.isArray(response.data)) {
            return response.data;
        }

        return [];
    }

    window.initAjaxAutocomplete = function (elements, options) {
        const settings = Object.assign({
            delay: 250,
            minimumInputLength: 1,
            mapResults: responseItems,
            mapItem: item => Object.assign({}, item, {
                id: item.id,
                text: item.text || item.name || ''
            }),
            onSelect: function () {},
            onClear: function () {}
        }, options || {});

        normalizeElements(elements).forEach(function (select) {
            if (select.dataset.autocompleteInitialized === 'true') {
                return;
            }

            select.dataset.autocompleteInitialized = 'true';
            const wasRequired = select.required;
            const wrapper = document.createElement('div');
            wrapper.className = 'ajax-autocomplete';

            const input = document.createElement('input');
            input.type = 'text';
            input.autocomplete = 'off';
            input.className = (select.className || '') + ' ajax-autocomplete-input';
            input.placeholder = settings.placeholder || select.options[0]?.text || 'Gõ để tìm kiếm...';
            input.disabled = select.disabled || settings.disabled;
            select.disabled = input.disabled;
            input.required = wasRequired && !select.disabled;
            input.setAttribute('role', 'combobox');
            input.setAttribute('aria-autocomplete', 'list');
            input.setAttribute('aria-expanded', 'false');

            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.className = 'ajax-autocomplete-clear';
            clearButton.setAttribute('aria-label', 'Xóa lựa chọn');
            clearButton.textContent = '\u00d7';
            clearButton.hidden = true;

            const dropdown = document.createElement('div');
            dropdown.className = 'ajax-autocomplete-dropdown';
            dropdown.id = 'ajax-autocomplete-' + (++autocompleteIndex);
            dropdown.hidden = true;
            dropdown.setAttribute('role', 'listbox');

            const field = document.createElement('div');
            field.className = 'ajax-autocomplete-field';
            field.append(input, clearButton);
            wrapper.append(field);
            select.before(wrapper);
            document.body.append(dropdown);
            select.hidden = true;
            select.required = false;
            input.setAttribute('aria-controls', dropdown.id);

            const selectedOption = select.options[select.selectedIndex];
            if (select.value && selectedOption) {
                input.value = selectedOption.text;
                clearButton.hidden = false;
            }

            let timer;
            let controller;
            let activeIndex = -1;
            let results = [];

            function positionDropdown() {
                const rect = input.getBoundingClientRect();
                dropdown.style.left = rect.left + 'px';
                dropdown.style.top = rect.bottom + 2 + 'px';
                dropdown.style.width = rect.width + 'px';
                dropdown.style.maxHeight = Math.max(80, window.innerHeight - rect.bottom - 8) + 'px';
            }

            function closeDropdown() {
                dropdown.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
            }

            function showMessage(message) {
                dropdown.replaceChildren();
                const row = document.createElement('div');
                row.className = 'ajax-autocomplete-message';
                row.textContent = message;
                dropdown.append(row);
                positionDropdown();
                dropdown.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            function choose(item) {
                const id = String(item.id);
                const text = String(item.text || '');
                const option = new Option(text, id, true, true);
                select.replaceChildren(option);
                select.value = id;
                input.value = text;
                input.setCustomValidity('');
                clearButton.hidden = false;
                closeDropdown();
                select.dispatchEvent(new Event('change', { bubbles: true }));
                settings.onSelect(item, select);
            }

            function renderResults(items) {
                results = items;
                dropdown.replaceChildren();
                activeIndex = -1;

                if (!items.length) {
                    showMessage(settings.noResultsText || 'Không tìm thấy kết quả');
                    return;
                }

                items.forEach(function (item, index) {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'ajax-autocomplete-option';
                    option.setAttribute('role', 'option');
                    option.textContent = item.suggestionText || item.full_text || item.text;

                    if (item.description) {
                        const description = document.createElement('span');
                        description.className = 'ajax-autocomplete-description';
                        description.textContent = item.description;
                        option.append(description);
                    }

                    option.addEventListener('mouseenter', function () {
                        activeIndex = index;
                        updateActiveOption();
                    });
                    option.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                        choose(item);
                    });
                    dropdown.append(option);
                });

                positionDropdown();
                dropdown.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            function updateActiveOption() {
                Array.from(dropdown.querySelectorAll('.ajax-autocomplete-option')).forEach(function (option, index) {
                    option.classList.toggle('is-active', index === activeIndex);
                });
            }

            function search(query) {
                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();
                showMessage('Đang tìm...');
                const url = new URL(typeof settings.url === 'function' ? settings.url() : settings.url, window.location.origin);
                const requestData = settings.getRequestData ? settings.getRequestData(query) : { q: query };
                Object.entries(requestData || {}).forEach(function (entry) {
                    url.searchParams.set(entry[0], entry[1]);
                });

                fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Search request failed');
                        }
                        return response.json();
                    })
                    .then(function (response) {
                        renderResults(settings.mapResults(response).map(settings.mapItem));
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError') {
                            showMessage('Không thể tải dữ liệu');
                        }
                    });
            }

            function clearSelection(preserveInput) {
                const inputValue = input.value;
                select.replaceChildren(new Option('', '', true, true));
                select.value = '';
                input.value = preserveInput ? inputValue : '';
                input.setCustomValidity('');
                clearButton.hidden = true;
                closeDropdown();
                select.dispatchEvent(new Event('change', { bubbles: true }));
                settings.onClear(select);
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                if (select.value) {
                    clearSelection(true);
                }

                const query = input.value.trim();
                clearButton.hidden = query.length === 0;
                input.setCustomValidity(wasRequired && query ? 'Vui lòng chọn một dòng gợi ý.' : '');
                if (query.length < settings.minimumInputLength) {
                    closeDropdown();
                    return;
                }

                timer = setTimeout(function () {
                    search(query);
                }, settings.delay);
            });

            input.addEventListener('keydown', function (event) {
                const options = dropdown.querySelectorAll('.ajax-autocomplete-option');

                if (event.key === 'ArrowDown' && options.length) {
                    event.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, options.length - 1);
                    updateActiveOption();
                } else if (event.key === 'ArrowUp' && options.length) {
                    event.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                    updateActiveOption();
                } else if (event.key === 'Enter' && activeIndex >= 0 && results[activeIndex]) {
                    event.preventDefault();
                    choose(results[activeIndex]);
                } else if (event.key === 'Escape') {
                    closeDropdown();
                }
            });

            clearButton.addEventListener('click', clearSelection);
            document.addEventListener('click', function (event) {
                if (!wrapper.contains(event.target) && !dropdown.contains(event.target)) {
                    closeDropdown();
                }
            });
            window.addEventListener('resize', function () {
                if (!dropdown.hidden) positionDropdown();
            });
            window.addEventListener('scroll', function () {
                if (!dropdown.hidden) positionDropdown();
            }, true);
        });
    };

    if (window.jQuery) {
        window.jQuery.fn.autocompleteSelect = function (options) {
            const autocompleteOptions = options || {};
            const ajaxOptions = autocompleteOptions.ajax || {};

            window.initAjaxAutocomplete(this, {
                url: ajaxOptions.url,
                placeholder: autocompleteOptions.placeholder,
                minimumInputLength: autocompleteOptions.minimumInputLength || 1,
                disabled: autocompleteOptions.disabled,
                getRequestData: function (query) {
                    return ajaxOptions.data ? ajaxOptions.data({ term: query }) : { q: query };
                },
                mapResults: function (response) {
                    if (!ajaxOptions.processResults) {
                        return responseItems(response);
                    }

                    const processed = ajaxOptions.processResults(response, { term: '' });
                    return processed && Array.isArray(processed.results) ? processed.results : [];
                },
                onSelect: function (item, select) {
                    const event = window.jQuery.Event('autocomplete:select');
                    event.params = { data: item };
                    window.jQuery(select).trigger(event);
                },
                onClear: function (select) {
                    window.jQuery(select).trigger('autocomplete:clear');
                }
            });

            return this;
        };
    }
})();