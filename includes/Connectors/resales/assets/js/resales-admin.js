/**
 * Resales Admin JavaScript
 *
 * Maneja la funcionalidad del botón "Probar conexión" en la pestaña Resales
 * de los ajustes del chat.
 *
 * @package AI360Chat
 * @subpackage Addons/Resales
 */

(function() {
    'use strict';

    /**
     * Inicializa el manejador del botón de test de conexión.
     */
    function init() {
        var testButton = document.getElementById('ai360chat_resales_test_connection');
        var resultDiv = document.getElementById('ai360chat_resales_test_result');

        if (!testButton || !resultDiv) {
            return;
        }

        testButton.addEventListener('click', function(e) {
            e.preventDefault();
            testConnection(testButton, resultDiv);
        });
    }

    /**
     * Shows a loading spinner in the result area.
     *
     * @param {HTMLElement} result  The element to show the spinner in.
     */
    function showLoadingSpinner(result) {
        result.innerHTML = '<span class="ai360chat-spinner" style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #e67e22; border-radius: 50%; animation: ai360chat-spin 1s linear infinite; vertical-align: middle; margin-right: 10px;"></span>' +
            '<span style="vertical-align: middle; color: #666;">' + (ai360chatResalesAdmin.i18n.testing || 'Probando conexión...') + '</span>';
        result.style.background = '#fff9e6';
        result.style.borderLeft = '4px solid #e67e22';

        // Add keyframes for spinner animation if not already present
        if (!document.getElementById('ai360chat-spinner-style')) {
            var style = document.createElement('style');
            style.id = 'ai360chat-spinner-style';
            style.textContent = '@keyframes ai360chat-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.head.appendChild(style);
        }
    }

    /**
     * Shows a success message in the result area.
     *
     * @param {HTMLElement} result  The element to show the message in.
     * @param {string}      message The success message.
     */
    function showSuccess(result, message) {
        result.innerHTML = '<span style="color: #46b450; font-weight: bold;">✓ </span><span style="color: #1d2327;">' + escapeHtml(message) + '</span>';
        result.style.background = '#ecf7ed';
        result.style.borderLeft = '4px solid #46b450';
        result.style.whiteSpace = 'normal';
    }

    /**
     * Shows an error message in the result area.
     *
     * @param {HTMLElement} result  The element to show the message in.
     * @param {string}      message The error message.
     * @param {string}      hint    Optional hint text.
     */
    function showError(result, message, hint) {
        var html = '<span style="color: #dc3232; font-weight: bold;">✗ </span><span style="color: #1d2327;">' + escapeHtml(message) + '</span>';
        if (hint) {
            html += '<br><span style="color: #666; font-size: 0.9em;">💡 ' + escapeHtml(hint) + '</span>';
        }
        result.innerHTML = html;
        result.style.background = '#fbeaea';
        result.style.borderLeft = '4px solid #dc3232';
        result.style.whiteSpace = 'normal';
    }

    /**
     * Escapes HTML special characters to prevent XSS.
     *
     * @param {string} text The text to escape.
     * @return {string} The escaped text.
     */
    function escapeHtml(text) {
        if (text == null) {
            return '';
        }
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    /**
     * Realiza la llamada al endpoint de test de conexión.
     *
     * @param {HTMLElement} button  El botón que disparó la acción.
     * @param {HTMLElement} result  El elemento donde mostrar el resultado.
     */
    function testConnection(button, result) {
        // Store original button text
        var originalButtonText = button.textContent;

        // Deshabilitar botón mientras se ejecuta
        button.disabled = true;
        button.textContent = ai360chatResalesAdmin.i18n.testing || 'Probando...';
        button.style.opacity = '0.7';
        button.style.cursor = 'wait';

        // Show loading spinner
        showLoadingSpinner(result);

        // Realizar llamada REST
        fetch(ai360chatResalesAdmin.restUrl + '/test-resales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ai360chatResalesAdmin.nonce
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                showSuccess(result, data.message);
            } else {
                var hint = (data.details && data.details.hint) ? data.details.hint : null;
                showError(result, data.message, hint);
            }
        })
        .catch(function(error) {
            showError(result, ai360chatResalesAdmin.i18n.error || 'Error de conexión', null);
        })
        .finally(function() {
            // Rehabilitar botón
            button.disabled = false;
            button.textContent = originalButtonText;
            button.style.opacity = '';
            button.style.cursor = '';
        });
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
