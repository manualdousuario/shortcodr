(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {

        // ===== UTILITY FUNCTIONS =====
        // AJAX Helper
        function ajax(url, options) {
            return fetch(url, {
                method: options.method || 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(options.data || {})
            }).then(function(response) {
                return response.json();
            });
        }
    
        // ===== COPY URL TO CLIPBOARD =====
        document.querySelectorAll('.shortcodr-copy-url').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var url = this.getAttribute('data-url');
                
                // Try to copy to clipboard
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function() {
                        showCopySuccess(button);
                    }).catch(function() {
                        fallbackCopyToClipboard(url, button);
                    });
                } else {
                    fallbackCopyToClipboard(url, button);
                }
            });
        });

        // Fallback copy method
        function fallbackCopyToClipboard(text, button) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess(button);
            } catch (err) {
                console.error('Could not copy text: ', err);
                alert('Could not copy URL to clipboard');
            }
            
            document.body.removeChild(textArea);
        }

        // Show copy success feedback
        function showCopySuccess(button) {
            var originalTitle = button.getAttribute('title');
            var originalClass = button.className;
            
            button.classList.add('shortcodr-copy-success');
            button.setAttribute('title', 'Copied!');
            
            setTimeout(function() {
                button.classList.remove('shortcodr-copy-success');
                button.setAttribute('title', originalTitle);
            }, 2000);
        }

        // ===== GENERATE RANDOM SLUG =====
        var generateSlugBtn = document.getElementById('generate-slug');
        if (generateSlugBtn) {
            generateSlugBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var button = this;
                var originalText = button.textContent;
                
                button.textContent = 'Generating...';
                button.disabled = true;
                
                ajax(shortcodr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortcodr_generate_slug',
                        nonce: shortcodr_ajax.nonce
                    }
                }).then(function(response) {
                    if (response.success) {
                        document.getElementById('slug').value = response.data.slug;
                        updateSlugPreview();
                    } else {
                        alert('Error generating slug: ' + response.data);
                    }
                }).catch(function() {
                    alert('Error generating slug');
                }).finally(function() {
                    button.textContent = originalText;
                    button.disabled = false;
                });
            });
        }

        // ===== UPDATE SLUG PREVIEW =====
        var slugInput = document.getElementById('slug');
        if (slugInput) {
            slugInput.addEventListener('input', updateSlugPreview);
        }

        function updateSlugPreview() {
            var slug = document.getElementById('slug').value || '[slug]';
            var previewUrl = document.getElementById('preview-url');
            if (previewUrl) {
                var baseUrl = previewUrl.textContent.split('/');
                baseUrl[baseUrl.length - 1] = slug;
                previewUrl.textContent = baseUrl.join('/');
            }
        }

        // ===== TOGGLE URL STATUS =====
        document.querySelectorAll('.shortcodr-toggle-status').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var currentStatus = this.getAttribute('data-status');
                var row = document.getElementById('url-' + id);
                
                button.disabled = true;
                
                ajax(shortcodr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortcodr_toggle_status',
                        nonce: shortcodr_ajax.nonce,
                        id: id,
                        current_status: currentStatus
                    }
                }).then(function(response) {
                    if (response.success) {
                        var newStatus = response.data.new_status;
                        
                        // Update button
                        button.setAttribute('data-status', newStatus);
                        button.textContent = newStatus === 'active' ? 'Deactivate' : 'Activate';
                        
                        // Update status column
                        var statusCol = row.querySelector('.column-status .shortcodr-status');
                        if (statusCol) {
                            statusCol.classList.remove('shortcodr-status-active', 'shortcodr-status-inactive');
                            statusCol.classList.add('shortcodr-status-' + newStatus);
                            statusCol.textContent = newStatus === 'active' ? 'Active' : 'Inactive';
                        }
                    } else {
                        alert('Error updating status: ' + response.data);
                    }
                }).catch(function() {
                    alert('Error updating status');
                }).finally(function() {
                    button.disabled = false;
                });
            });
        });

        // ===== DELETE URL =====
        document.querySelectorAll('.shortcodr-delete-url').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!confirm(shortcodr_ajax.confirm_delete)) {
                    return;
                }
                
                var id = this.getAttribute('data-id');
                var row = document.getElementById('url-' + id);
                
                button.disabled = true;
                
                ajax(shortcodr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortcodr_delete_url',
                        nonce: shortcodr_ajax.nonce,
                        id: id
                    }
                }).then(function(response) {
                    if (response.success) {
                        row.style.opacity = '0';
                        setTimeout(function() {
                            row.remove();
                        }, 400);
                    } else {
                        alert('Error deleting URL: ' + response.data);
                    }
                }).catch(function() {
                    alert('Error deleting URL');
                }).finally(function() {
                    button.disabled = false;
                });
            });
        });

        // ===== VALIDATE SLUG INPUT =====
        if (slugInput) {
            slugInput.addEventListener('input', function() {
                var value = this.value;
                var cleanValue = value.replace(/[^a-zA-Z0-9\-_]/g, '');
                
                if (value !== cleanValue) {
                    this.value = cleanValue;
                }
            });
        }

        // ===== FORM VALIDATION =====
        document.querySelectorAll('.shortcodr-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var targetUrl = form.querySelector('#target_url');
                var slug = form.querySelector('#slug');
                
                // Validate target URL
                if (targetUrl && (!targetUrl.value || !isValidUrl(targetUrl.value))) {
                    alert('Please enter a valid target URL');
                    e.preventDefault();
                    return false;
                }
                
                // Validate slug if provided
                if (slug && slug.value && !/^[a-zA-Z0-9\-_]+$/.test(slug.value)) {
                    alert('Slug can only contain letters, numbers, hyphens and underscores');
                    e.preventDefault();
                    return false;
                }
            });
        });

        // URL validation function
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        // ===== DELETE CAMPAIGN CONFIRMATION =====
        document.querySelectorAll('.shortcodr-delete-campaign').forEach(function(link) {
            link.addEventListener('click', function(e) {
                var urlCount = parseInt(this.getAttribute('data-url-count'));
                var message = 'Are you sure you want to delete this campaign?';

                if (urlCount > 0) {
                    message += '\n\nThis campaign has ' + urlCount + ' URLs that will be unassigned.';
                }

                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });

        // ===== SETTINGS PAGE - URL PATTERN VALIDATION =====
        var patternInput = document.getElementById('shortcodr_base_url_pattern');
        var validationMsg = document.getElementById('pattern-validation-message');
        var submitBtn = document.querySelector('input[type="submit"]');
        var validationTimeout;

        if (patternInput && validationMsg) {
            // Update URL preview when base pattern changes
            patternInput.addEventListener('input', function() {
                var baseUrl = this.getAttribute('data-base-url') || window.location.origin + '/';
                var pattern = this.value.trim();
                if (pattern === '') {
                    pattern = 'go';
                }
                var preview = baseUrl + pattern + '/example';
                var previewElement = document.getElementById('shortcodr-url-preview');
                if (previewElement) {
                    previewElement.textContent = preview;
                }
                
                // Clear previous timeout
                clearTimeout(validationTimeout);
                
                // Validate pattern after user stops typing
                validationTimeout = setTimeout(function() {
                    validatePattern(pattern);
                }, 500);
            });
        }

        // Validate pattern function
        function validatePattern(pattern) {
            if (pattern === '') {
                pattern = 'go';
            }
            
            var nonce = document.querySelector('input[name="shortcodr_settings_nonce"]');
            if (!nonce) return;
            
            ajax(window.location.origin + '/wp-admin/admin-ajax.php', {
                method: 'POST',
                data: {
                    action: 'shortcodr_validate_pattern',
                    nonce: nonce.value,
                    pattern: pattern
                }
            }).then(function(response) {
                if (response.success) {
                    validationMsg.classList.remove('notice-error');
                    validationMsg.classList.add('notice-success');
                    validationMsg.style.background = '#d4edda';
                    validationMsg.style.border = '1px solid #c3e6cb';
                    validationMsg.style.color = '#155724';
                    validationMsg.innerHTML = '<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message;
                    validationMsg.style.display = 'block';
                    if (submitBtn) submitBtn.disabled = false;
                } else {
                    validationMsg.classList.remove('notice-success');
                    validationMsg.classList.add('notice-error');
                    validationMsg.style.background = '#f8d7da';
                    validationMsg.style.border = '1px solid #f5c6cb';
                    validationMsg.style.color = '#721c24';
                    validationMsg.innerHTML = '<span class="dashicons dashicons-warning"></span> ' + response.data.message;
                    validationMsg.style.display = 'block';
                    if (submitBtn) submitBtn.disabled = true;
                }
            }).catch(function() {
                validationMsg.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            });
        }

        // Ensure at least administrator is always checked
        document.querySelectorAll('input[name="shortcodr_user_capabilities[]"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var checkedBoxes = document.querySelectorAll('input[name="shortcodr_user_capabilities[]"]:checked');
                var adminCheckbox = document.getElementById('shortcodr_capability_administrator');
                
                if (checkedBoxes.length === 0 && adminCheckbox) {
                    adminCheckbox.checked = true;
                    alert('At least one user role must be selected. Administrator has been automatically selected.');
                }
            });
        });

        // ===== IMPORT/EXPORT PAGE - EXPORT JSON =====
        var exportJsonBtn = document.getElementById('export-json-btn');
        var exportStatus = document.getElementById('export-status');
        
        if (exportJsonBtn && exportStatus) {
            exportJsonBtn.addEventListener('click', function() {
                var btn = this;
                var originalHTML = btn.innerHTML;
                
                btn.disabled = true;
                btn.textContent = 'Exporting...';
                exportStatus.style.display = 'none';
                
                ajax(shortcodr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortcodr_export_json',
                        nonce: shortcodr_ajax.nonce
                    }
                }).then(function(response) {
                    if (response.success) {
                        // Create download link
                        var blob = new Blob([response.data.json], {type: 'application/json'});
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'shortcodr-export-' + new Date().toISOString().split('T')[0] + '.json';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        exportStatus.classList.remove('error');
                        exportStatus.classList.add('success');
                        exportStatus.textContent = 'Export successful! File downloaded.';
                        exportStatus.style.display = 'block';
                    } else {
                        exportStatus.classList.remove('success');
                        exportStatus.classList.add('error');
                        exportStatus.textContent = 'Export failed. Please try again.';
                        exportStatus.style.display = 'block';
                    }
                }).catch(function() {
                    exportStatus.classList.remove('success');
                    exportStatus.classList.add('error');
                    exportStatus.textContent = 'Export failed. Please try again.';
                    exportStatus.style.display = 'block';
                }).finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                });
            });
        }

    });

})();