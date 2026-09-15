document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = (window.PPCOSTA_BASE_URL || '').replace(/\/$/, '');
    document.querySelectorAll('[data-gallery-thumb]').forEach((button) => {
        button.addEventListener('click', () => {
            const main = document.querySelector('[data-gallery-main]');
            if (!main) return;
            main.src = button.querySelector('img').src;
            main.alt = button.getAttribute('aria-label');
            document.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => thumb.setAttribute('aria-pressed', String(thumb === button)));
        });
    });
    const sameBilling = document.querySelector('#same_billing');
    if (sameBilling) {
        const delivery = document.querySelector('.checkout-delivery-grid');
        const updateDelivery = () => {
            delivery.hidden = sameBilling.checked;
            delivery.querySelectorAll('input').forEach((input) => {
                input.disabled = sameBilling.checked;
                input.required = !sameBilling.checked;
            });
        };
        sameBilling.addEventListener('change', updateDelivery);
        updateDelivery();
    }
    const checkoutSummary = document.querySelector('[data-checkout-summary]');
    if (checkoutSummary) {
        const currency = (value) => new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(value);
        const updateShipping = () => {
            const selected = document.querySelector('input[name="shipping_method"]:checked');
            const shipping = checkoutSummary.dataset.freeShipping === '1' ? 0 : Number(selected?.dataset.shippingPrice || 0);
            checkoutSummary.querySelector('[data-checkout-shipping]').textContent = shipping ? currency(shipping) : 'Gratis';
            checkoutSummary.querySelector('[data-checkout-total]').textContent = currency(Math.max(0, Number(checkoutSummary.dataset.subtotal) - Number(checkoutSummary.dataset.discount) + shipping));
        };
        document.querySelectorAll('input[name="shipping_method"]').forEach((input) => input.addEventListener('change', updateShipping));
        updateShipping();
    }
    const newsletterForms = document.querySelectorAll('[data-newsletter-form]');

    newsletterForms.forEach((newsletterForm) => {
        newsletterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const input = newsletterForm.querySelector('input[type="email"]');
            const message = newsletterForm.querySelector('[data-newsletter-message]');
            const button = newsletterForm.querySelector('button[type="submit"]');

            if (input && input.value.trim()) {
                button.disabled = true;

                fetch(`${baseUrl}/api/newsletter.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email: input.value.trim(), csrf_token: window.PPCOSTA_CSRF_TOKEN }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (message) {
                            message.textContent = data.message || 'Subscricao registada.';
                            message.className = data.status === 'ok' ? 'form-text text-success' : 'form-text text-danger';
                        }

                        if (data.status === 'ok') {
                            input.value = '';
                        }
                    })
                    .catch(() => {
                        if (message) {
                            message.textContent = 'Nao foi possivel registar a subscricao neste momento.';
                            message.className = 'form-text text-danger';
                        }
                    })
                    .finally(() => {
                        button.disabled = false;
                    });
            }
        });
    });

    const personalizationForm = document.querySelector('[data-personalization-form]');

    if (personalizationForm) {
        const previewText = document.querySelector('[data-preview-text]');
        const previewMeta = document.querySelector('[data-preview-meta]');
        const extraOutput = personalizationForm.querySelector('[data-personalization-extra]');
        const totalOutput = personalizationForm.querySelector('[data-personalization-total]');
        const uploadedFileInput = personalizationForm.querySelector('[data-uploaded-personalization-file]');
        const uploadMessage = personalizationForm.querySelector('[data-upload-message]');
        const variationSelect = personalizationForm.querySelector('[data-variation-select]');
        const basePrice = Number.parseFloat(personalizationForm.dataset.productPrice || '0');
        const productId = Number.parseInt(personalizationForm.dataset.productId || '0', 10);
        const currentBasePrice = () => {
            const selectedVariation = variationSelect?.selectedOptions?.[0];
            return basePrice + Number.parseFloat(selectedVariation?.dataset.priceDelta || '0');
        };

        const collectValues = () => {
            const values = {};

            personalizationForm.querySelectorAll('[data-personalization-field]').forEach((field) => {
                if ((field.type === 'radio' || field.type === 'checkbox') && !field.checked) {
                    return;
                }

                const key = field.dataset.previewField;

                if (key && field.value) {
                    values[key] = field.value;
                }
            });

            const fileField = personalizationForm.querySelector('[data-personalization-file]');

            if (fileField && fileField.files.length > 0) {
                values[fileField.dataset.previewField || 'ficheiro'] = fileField.files[0].name;
            }

            return values;
        };

        const updatePreview = () => {
            const values = collectValues();
            const text = values.nome || values.texto || '';
            const metaParts = [];

            if (values.tecnica) {
                metaParts.push(values.tecnica.toUpperCase());
            }

            if (values.posicao) {
                metaParts.push(values.posicao);
            }

            if (values.ficheiro) {
                metaParts.push(values.ficheiro);
            }

            if (previewText) {
                previewText.textContent = text;
                previewText.style.color = personalizationForm.querySelector('[data-preview-field="cor"]:checked')?.dataset.color || values.cor || '';
                previewText.dataset.font = values.fonte || '';
            }

            if (previewMeta) {
                previewMeta.textContent = metaParts.join(' · ');
            }
        };

        let priceRequest = 0;
        const updatePrice = () => {
            const requestId = ++priceRequest;
            fetch(`${baseUrl}/api/personalization-price.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    variation_id: Number(variationSelect?.value || 0),
                    values: collectValues(),
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (requestId !== priceRequest) return;
                    if (data.status === 'ok') {
                        if (extraOutput) {
                            extraOutput.textContent = data.extra_formatted;
                        }

                        if (totalOutput) {
                            totalOutput.textContent = data.total_formatted;
                        }
                    }
                })
                .catch(() => {
                    if (requestId !== priceRequest) return;
                    const localExtra = Array.from(personalizationForm.querySelectorAll('[data-personalization-field], [data-personalization-file]')).reduce((sum, field) => {
                        if ((field.type === 'radio' || field.type === 'checkbox') && !field.checked) {
                            return sum;
                        }

                        if (field.type === 'file' && field.files.length === 0) {
                            return sum;
                        }

                        if (field.type !== 'file' && !field.value) {
                            return sum;
                        }

                        return sum + Number.parseFloat(field.dataset.baseExtraPrice || '0')
                            + Number.parseFloat(field.selectedOptions?.[0]?.dataset.extraPrice || field.dataset.extraPrice || '0');
                    }, 0);

                    if (extraOutput) {
                        extraOutput.textContent = `${localExtra.toFixed(2).replace('.', ',')} EUR`;
                    }

                    if (totalOutput) {
                        totalOutput.textContent = `${(currentBasePrice() + localExtra).toFixed(2).replace('.', ',')} EUR`;
                    }
                });
        };

        let priceTimer;
        personalizationForm.querySelectorAll('[data-personalization-field], [data-personalization-file], [data-variation-select]').forEach((field) => {
            field.addEventListener('input', () => {
                updatePreview();
                clearTimeout(priceTimer);
                priceTimer = setTimeout(updatePrice, 180);
            });
            field.addEventListener('change', () => {
                updatePreview();
                clearTimeout(priceTimer);
                priceTimer = setTimeout(updatePrice, 180);
            });
        });

        const fileField = personalizationForm.querySelector('[data-personalization-file]');

        if (fileField) {
            let uploadRequest = 0;
            const submitButton = personalizationForm.querySelector('button[type="submit"]');
            const initiallyDisabled = submitButton.disabled;
            fileField.addEventListener('change', () => {
                const requestId = ++uploadRequest;
                uploadedFileInput.value = '';
                fileField.setCustomValidity('');
                submitButton.disabled = initiallyDisabled;
                if (!fileField.files.length) {
                    if (uploadedFileInput) {
                        uploadedFileInput.value = '';
                    }
                    return;
                }

                const formData = new FormData();
                submitButton.disabled = true;
                fileField.setCustomValidity('Aguarda a conclusao do carregamento.');
                formData.append('file', fileField.files[0]);

                const csrfInput = personalizationForm.querySelector('input[name="csrf_token"]');

                if (csrfInput) {
                    formData.append('csrf_token', csrfInput.value);
                }

                if (uploadMessage) {
                    uploadMessage.textContent = 'A carregar ficheiro...';
                    uploadMessage.className = 'form-text text-secondary';
                }

                fetch(`${baseUrl}/api/personalization-upload.php`, {
                    method: 'POST',
                    body: formData,
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (requestId !== uploadRequest) return;
                        if (data.status === 'ok') {
                            fileField.setCustomValidity('');
                            if (uploadedFileInput) {
                                uploadedFileInput.value = data.path;
                            }

                            if (uploadMessage) {
                                uploadMessage.textContent = data.message;
                                uploadMessage.className = 'form-text text-success';
                            }
                        } else if (uploadMessage) {
                            fileField.setCustomValidity('O ficheiro nao foi carregado. Escolhe outro ficheiro.');
                            uploadMessage.textContent = data.message || 'Ficheiro invalido.';
                            uploadMessage.className = 'form-text text-danger';
                        }
                    })
                    .catch(() => {
                        if (requestId !== uploadRequest) return;
                        fileField.setCustomValidity('Nao foi possivel carregar o ficheiro. Tenta novamente.');
                        if (uploadMessage) {
                            uploadMessage.textContent = 'Nao foi possivel carregar o ficheiro neste momento.';
                            uploadMessage.className = 'form-text text-danger';
                        }
                    })
                    .finally(() => {
                        if (requestId === uploadRequest) submitButton.disabled = initiallyDisabled;
                    });
            });
        }

        updatePreview();
        updatePrice();
    }

    const adminThemeToggle = document.querySelector('[data-admin-theme-toggle]');

    if (adminThemeToggle) {
        const applyTheme = (dark) => {
            document.body.classList.toggle('admin-dark', dark);
            adminThemeToggle.textContent = dark ? 'Modo claro' : 'Modo escuro';
            adminThemeToggle.setAttribute('aria-pressed', String(dark));
        };
        try { applyTheme(localStorage.getItem('ppcosta-theme') === 'dark'); } catch (_) { /* Storage may be unavailable. */ }
        adminThemeToggle.addEventListener('click', () => {
            applyTheme(!document.body.classList.contains('admin-dark'));
            try { localStorage.setItem('ppcosta-theme', document.body.classList.contains('admin-dark') ? 'dark' : 'light'); } catch (_) { /* Keep the current theme in memory. */ }
        });
    }
});
