(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {

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
    
        document.querySelectorAll('.shortlinkr-copy-url').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var url = this.getAttribute('data-url');
                
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

        function showCopySuccess(button) {
            var originalTitle = button.getAttribute('title');
            var originalClass = button.className;
            
            button.classList.add('shortlinkr-copy-success');
            button.setAttribute('title', 'Copied!');
            
            setTimeout(function() {
                button.classList.remove('shortlinkr-copy-success');
                button.setAttribute('title', originalTitle);
            }, 2000);
        }

        var generateSlugBtn = document.getElementById('generate-slug');
        if (generateSlugBtn) {
            generateSlugBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var button = this;
                var originalText = button.textContent;
                
                button.textContent = 'Generating...';
                button.disabled = true;
                
                ajax(shortlinkr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortlinkr_generate_slug',
                        nonce: shortlinkr_ajax.nonce
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

        document.querySelectorAll('.shortlinkr-toggle-status').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var currentStatus = this.getAttribute('data-status');
                var row = document.getElementById('url-' + id);
                
                button.disabled = true;
                
                ajax(shortlinkr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortlinkr_toggle_status',
                        nonce: shortlinkr_ajax.nonce,
                        id: id,
                        current_status: currentStatus
                    }
                }).then(function(response) {
                    if (response.success) {
                        var newStatus = response.data.new_status;
                        
                        button.setAttribute('data-status', newStatus);
                        button.textContent = newStatus === 'active' ? 'Deactivate' : 'Activate';
                        
                        var statusCol = row.querySelector('.column-status .shortlinkr-status');
                        if (statusCol) {
                            statusCol.classList.remove('shortlinkr-status-active', 'shortlinkr-status-inactive');
                            statusCol.classList.add('shortlinkr-status-' + newStatus);
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

        document.querySelectorAll('.shortlinkr-delete-url').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!confirm(shortlinkr_ajax.confirm_delete)) {
                    return;
                }
                
                var id = this.getAttribute('data-id');
                var row = document.getElementById('url-' + id);
                
                button.disabled = true;
                
                ajax(shortlinkr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortlinkr_delete_url',
                        nonce: shortlinkr_ajax.nonce,
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

        if (slugInput) {
            slugInput.addEventListener('input', function() {
                var value = this.value;
                var cleanValue = value.replace(/[^a-zA-Z0-9\-_]/g, '');
                
                if (value !== cleanValue) {
                    this.value = cleanValue;
                }
            });
        }

        document.querySelectorAll('.shortlinkr-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var targetUrl = form.querySelector('#target_url');
                var slug = form.querySelector('#slug');
                
                if (targetUrl && (!targetUrl.value || !isValidUrl(targetUrl.value))) {
                    alert('Please enter a valid target URL');
                    e.preventDefault();
                    return false;
                }
                
                if (slug && slug.value && !/^[a-zA-Z0-9\-_]+$/.test(slug.value)) {
                    alert('Slug can only contain letters, numbers, hyphens and underscores');
                    e.preventDefault();
                    return false;
                }
            });
        });

        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        document.querySelectorAll('.shortlinkr-delete-campaign').forEach(function(link) {
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

        var patternInput = document.getElementById('shortlinkr_base_url_pattern');
        var validationMsg = document.getElementById('pattern-validation-message');
        var submitBtn = document.querySelector('input[type="submit"]');
        var validationTimeout;

        if (patternInput && validationMsg) {
            patternInput.addEventListener('input', function() {
                var baseUrl = this.getAttribute('data-base-url') || window.location.origin + '/';
                var pattern = this.value.trim();
                if (pattern === '') {
                    pattern = 'go';
                }
                var preview = baseUrl + pattern + '/example';
                var previewElement = document.getElementById('shortlinkr-url-preview');
                if (previewElement) {
                    previewElement.textContent = preview;
                }
                
                clearTimeout(validationTimeout);
                
                validationTimeout = setTimeout(function() {
                    validatePattern(pattern);
                }, 500);
            });
        }

        function validatePattern(pattern) {
            if (pattern === '') {
                pattern = 'go';
            }
            
            var nonce = document.querySelector('input[name="shortlinkr_settings_nonce"]');
            if (!nonce) return;
            
            ajax(window.location.origin + '/wp-admin/admin-ajax.php', {
                method: 'POST',
                data: {
                    action: 'shortlinkr_validate_pattern',
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

        document.querySelectorAll('input[name="shortlinkr_user_capabilities[]"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var checkedBoxes = document.querySelectorAll('input[name="shortlinkr_user_capabilities[]"]:checked');
                var adminCheckbox = document.getElementById('shortlinkr_capability_administrator');
                
                if (checkedBoxes.length === 0 && adminCheckbox) {
                    adminCheckbox.checked = true;
                    alert('At least one user role must be selected. Administrator has been automatically selected.');
                }
            });
        });

        var exportJsonBtn = document.getElementById('export-json-btn');
        var exportStatus = document.getElementById('export-status');
        
        if (exportJsonBtn && exportStatus) {
            exportJsonBtn.addEventListener('click', function() {
                var btn = this;
                var originalHTML = btn.innerHTML;
                
                btn.disabled = true;
                btn.textContent = 'Exporting...';
                exportStatus.style.display = 'none';
                
                ajax(shortlinkr_ajax.ajax_url, {
                    method: 'POST',
                    data: {
                        action: 'shortlinkr_export_json',
                        nonce: shortlinkr_ajax.nonce
                    }
                }).then(function(response) {
                    if (response.success) {
                        var blob = new Blob([response.data.json], {type: 'application/json'});
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'shortlinkr-export-' + new Date().toISOString().split('T')[0] + '.json';
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