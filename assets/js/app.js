document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = window.PPCOSTA_BASE_URL || '';
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
                    body: JSON.stringify({ email: input.value.trim() }),
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
        const basePrice = Number.parseFloat(personalizationForm.dataset.productPrice || '0');
        const productId = Number.parseInt(personalizationForm.dataset.productId || '0', 10);

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
                values.ficheiro = fileField.files[0].name;
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
                previewText.style.color = values.cor || '';
                previewText.dataset.font = values.fonte || '';
            }

            if (previewMeta) {
                previewMeta.textContent = metaParts.join(' · ');
            }
        };

        const updatePrice = () => {
            fetch(`${baseUrl}/api/personalization-price.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    base_price: basePrice,
                    values: collectValues(),
                }),
            })
                .then((response) => response.json())
                .then((data) => {
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

                        return sum + Number.parseFloat(field.dataset.baseExtraPrice || field.selectedOptions?.[0]?.dataset.extraPrice || field.dataset.extraPrice || '0');
                    }, 0);

                    if (extraOutput) {
                        extraOutput.textContent = `${localExtra.toFixed(2).replace('.', ',')} EUR`;
                    }

                    if (totalOutput) {
                        totalOutput.textContent = `${(basePrice + localExtra).toFixed(2).replace('.', ',')} EUR`;
                    }
                });
        };

        personalizationForm.querySelectorAll('[data-personalization-field], [data-personalization-file]').forEach((field) => {
            field.addEventListener('input', () => {
                updatePreview();
                updatePrice();
            });
            field.addEventListener('change', () => {
                updatePreview();
                updatePrice();
            });
        });

        const fileField = personalizationForm.querySelector('[data-personalization-file]');

        if (fileField) {
            fileField.addEventListener('change', () => {
                if (!fileField.files.length) {
                    if (uploadedFileInput) {
                        uploadedFileInput.value = '';
                    }
                    return;
                }

                const formData = new FormData();
                formData.append('file', fileField.files[0]);

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
                        if (data.status === 'ok') {
                            if (uploadedFileInput) {
                                uploadedFileInput.value = data.path;
                            }

                            if (uploadMessage) {
                                uploadMessage.textContent = data.message;
                                uploadMessage.className = 'form-text text-success';
                            }
                        } else if (uploadMessage) {
                            uploadMessage.textContent = data.message || 'Ficheiro invalido.';
                            uploadMessage.className = 'form-text text-danger';
                        }
                    })
                    .catch(() => {
                        if (uploadMessage) {
                            uploadMessage.textContent = 'Nao foi possivel carregar o ficheiro neste momento.';
                            uploadMessage.className = 'form-text text-danger';
                        }
                    });
            });
        }

        updatePreview();
        updatePrice();
    }

    const adminThemeToggle = document.querySelector('[data-admin-theme-toggle]');

    if (adminThemeToggle) {
        adminThemeToggle.addEventListener('click', () => {
            document.body.classList.toggle('admin-dark');
            adminThemeToggle.textContent = document.body.classList.contains('admin-dark') ? 'Modo claro' : 'Modo escuro';
        });
    }
});
