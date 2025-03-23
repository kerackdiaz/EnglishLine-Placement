(function($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    /**
     * Módulo para gestionar resultados
     */
    EnglishLineTest.ResultsManager = {
        // Elementos DOM
        $resultsTable: null,
        $bulkActionsSelect: null,
        $applyButton: null,
        $filterForm: null,
        
        /**
         * Inicializa el gestor de resultados
         */
        init: function() {
            this.$resultsTable = $('#results-table');
            
            if (!this.$resultsTable.length) {
                return;
            }
            
            this.$bulkActionsSelect = $('#bulk-action-selector-top');
            this.$applyButton = $('#doaction');
            this.$filterForm = $('#filter-form');
            
            // Vincular eventos
            this.bindEvents();
            
            // Inicializar DataTables si está disponible
            this.initDataTable();
        },
        
        /**
         * Vincula eventos para el gestor de resultados
         */
        bindEvents: function() {
            let self = this;
            
            // Seleccionar/deseleccionar todos
            $('#select-all-results').on('change', function() {
                let isChecked = $(this).prop('checked');
                $('.result-checkbox').prop('checked', isChecked);
                self.updateBulkActionsVisibility();
            });
            
            // Actualizar estado del "seleccionar todos" cuando cambian las casillas individuales
            this.$resultsTable.on('change', '.result-checkbox', function() {
                self.updateSelectAllState();
                self.updateBulkActionsVisibility();
            });
            
            // Manejar acciones en masa
            this.$applyButton.on('click', function(e) {
                e.preventDefault();
                self.applyBulkAction();
            });
            
            // Filtrado
            this.$filterForm.on('submit', function(e) {
                e.preventDefault();
                self.applyFilters();
            });
            
            // Exportar resultados
            $('#export-results-btn').on('click', function(e) {
                e.preventDefault();
                self.exportResults();
            });
            
            // Ver detalles de un resultado
            this.$resultsTable.on('click', '.view-result-btn', function(e) {
                e.preventDefault();
                let resultId = $(this).data('id');
                self.viewResultDetails(resultId);
            });
        },
        
        /**
         * Inicializa la tabla de datos con DataTables si está disponible
         */
        initDataTable: function() {
            if (typeof $.fn.DataTable !== 'undefined') {
                this.$resultsTable.DataTable({
                    responsive: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
                    },
                    columnDefs: [
                        { targets: 0, orderable: false }, 
                        { targets: -1, orderable: false }  
                    ],
                    order: [[1, 'desc']], 
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
                });
            }
        },
        
        /**
         * Actualiza el estado del checkbox "Seleccionar todos"
         */
        updateSelectAllState: function() {
            let allChecked = $('.result-checkbox:checked').length === $('.result-checkbox').length;
            $('#select-all-results').prop('checked', allChecked);
        },
        
        /**
         * Muestra/oculta las opciones de acciones en masa según las selecciones
         */
        updateBulkActionsVisibility: function() {
            let anyChecked = $('.result-checkbox:checked').length > 0;
            
            if (anyChecked) {
                $('.bulk-actions-container').addClass('is-active');
            } else {
                $('.bulk-actions-container').removeClass('is-active');
            }
        },
        
        /**
         * Aplica la acción en masa seleccionada
         */
        applyBulkAction: function() {
            let action = this.$bulkActionsSelect.val();
            
            if (!action || action === '-1') {
                alert('Por favor, selecciona una acción.');
                return;
            }
            
            let selectedIds = [];
            $('.result-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                alert('Por favor, selecciona al menos un resultado.');
                return;
            }
            
            // Confirmar acción
            let confirmMessage = '';
            
            switch (action) {
                case 'delete':
                    confirmMessage = '¿Estás seguro de que deseas eliminar los resultados seleccionados? Esta acción no se puede deshacer.';
                    break;
                case 'export':
                    confirmMessage = '¿Deseas exportar los resultados seleccionados?';
                    break;
                case 'mark-reviewed':
                    confirmMessage = '¿Deseas marcar los resultados seleccionados como revisados?';
                    break;
                case 'mark-pending':
                    confirmMessage = '¿Deseas marcar los resultados seleccionados como pendientes?';
                    break;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                return;
            }
            
            // Ejecutar acción
            switch (action) {
                case 'delete':
                    this.deleteResults(selectedIds);
                    break;
                case 'export':
                    this.exportSelectedResults(selectedIds);
                    break;
                case 'mark-reviewed':
                    this.updateResultsStatus(selectedIds, 'reviewed');
                    break;
                case 'mark-pending':
                    this.updateResultsStatus(selectedIds, 'pending');
                    break;
            }
        },
        
        /**
         * Elimina resultados seleccionados
         * 
         * @param {Array} ids - IDs de resultados a eliminar
         */
        deleteResults: function(ids) {
            let self = this;
            
            $.ajax({
                url: englishline_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'englishline_delete_results',
                    nonce: englishline_admin.nonce,
                    result_ids: ids
                },
                beforeSend: function() {
                    // Mostrar indicador de carga
                    self.$resultsTable.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        // Eliminar filas de la tabla
                        ids.forEach(function(id) {
                            $('#result-row-' + id).fadeOut(300, function() {
                                if (typeof self.$resultsTable.DataTable !== 'undefined') {
                                    self.$resultsTable.DataTable().row($(this)).remove().draw();
                                } else {
                                    $(this).remove();
                                }
                            });
                        });
                        
                        alert('Los resultados seleccionados han sido eliminados.');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error al procesar la solicitud. Por favor, inténtalo de nuevo.');
                },
                complete: function() {
                    self.$resultsTable.removeClass('loading');
                }
            });
        },
        
        /**
         * Exporta resultados seleccionados
         * 
         * @param {Array} ids - IDs de resultados a exportar
         */
        exportSelectedResults: function(ids) {
            let $form = $('<form>', {
                action: englishline_admin.ajax_url,
                method: 'POST',
                target: '_blank'
            });
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'englishline_export_results'
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: englishline_admin.nonce
            }));
            
            // Añadir cada ID como input separado
            ids.forEach(function(id) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: 'result_ids[]',
                    value: id
                }));
            });
            
            // Añadir al DOM, enviar y eliminar
            $form.appendTo('body').submit().remove();
        },
        
        /**
         * Actualiza el estado de resultados seleccionados
         * 
         * @param {Array} ids - IDs de resultados
         * @param {string} status - Nuevo estado
         */
        updateResultsStatus: function(ids, status) {
            let self = this;
            
            $.ajax({
                url: englishline_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'englishline_update_results_status',
                    nonce: englishline_admin.nonce,
                    result_ids: ids,
                    status: status
                },
                beforeSend: function() {
                    self.$resultsTable.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        let statusLabel = status === 'reviewed' ? 'Revisado' : 'Pendiente';
                        let statusClass = status === 'reviewed' ? 'status-reviewed' : 'status-pending';
                        
                        ids.forEach(function(id) {
                            let $row = $('#result-row-' + id);
                            $row.find('.result-status')
                                .removeClass('status-reviewed status-pending')
                                .addClass(statusClass)
                                .text(statusLabel);
                        });
                        
                        alert('Los estados de los resultados han sido actualizados.');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error al procesar la solicitud. Por favor, inténtalo de nuevo.');
                },
                complete: function() {
                    self.$resultsTable.removeClass('loading');
                }
            });
        },
        
        /**
         * Aplica filtros a la tabla de resultados
         */
        applyFilters: function() {
            let filterData = this.$filterForm.serialize();
            let self = this;
            
            $.ajax({
                url: englishline_admin.ajax_url,
                type: 'GET',
                data: filterData + '&action=englishline_filter_results',
                beforeSend: function() {
                    self.$resultsTable.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar tabla con nuevos datos
                        if (typeof self.$resultsTable.DataTable !== 'undefined') {
                            let dt = self.$resultsTable.DataTable();
                            dt.destroy();
                            
                            // Reemplazar contenido de la tabla
                            $('#results-table-container').html(response.data.html);
                            
                            // Reinicializar DataTable
                            self.$resultsTable = $('#results-table');
                            self.initDataTable();
                        } else {
                            $('#results-table-container').html(response.data.html);
                            self.$resultsTable = $('#results-table');
                        }
                        
                        // Re-vincular eventos
                        self.bindEvents();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error al procesar la solicitud. Por favor, inténtalo de nuevo.');
                },
                complete: function() {
                    self.$resultsTable.removeClass('loading');
                }
            });
        },
        
        /**
         * Exporta todos los resultados según los filtros actuales
         */
        exportResults: function() {
            let filterData = this.$filterForm.serialize();
            
            // Crear formulario dinámico para descargar el archivo
            let $form = $('<form>', {
                action: englishline_admin.ajax_url,
                method: 'POST',
                target: '_blank'
            });
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'englishline_export_results'
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: englishline_admin.nonce
            }));
            
            // Añadir filtros
            let filterParams = new URLSearchParams(filterData);
            filterParams.forEach(function(value, key) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: value
                }));
            });
            
            // Añadir al DOM, enviar y eliminar
            $form.appendTo('body').submit().remove();
        },
        
        /**
         * Muestra los detalles de un resultado en un modal
         * 
         * @param {number} resultId - ID del resultado a mostrar
         */
        viewResultDetails: function(resultId) {
            let self = this;
            
            $.ajax({
                url: englishline_admin.ajax_url,
                type: 'GET',
                data: {
                    action: 'englishline_get_result_details',
                    nonce: englishline_admin.nonce,
                    result_id: resultId
                },
                beforeSend: function() {
                    // Mostrar loading
                    $('#result-details-modal').remove();
                    $('body').append('<div id="result-details-modal" class="englishline-modal loading"><div class="modal-content"><div class="modal-loading">Cargando...</div></div></div>');
                    $('#result-details-modal').fadeIn(200);
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar contenido del modal
                        $('#result-details-modal .modal-content').html(response.data.html);
                        
                        // Inicializar grading si es necesario
                        if (typeof EnglishLineTest.GradingManager !== 'undefined') {
                            EnglishLineTest.GradingManager.init();
                        }
                    } else {
                        $('#result-details-modal .modal-content').html('<div class="error-message">Error: ' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $('#result-details-modal .modal-content').html('<div class="error-message">Error al cargar los detalles. Por favor, intenta de nuevo.</div>');
                },
                complete: function() {
                    $('#result-details-modal').removeClass('loading');
                    
                    // Vincular evento para cerrar el modal
                    $('#result-details-modal').on('click', '.close-modal', function(e) {
                        e.preventDefault();
                        $('#result-details-modal').fadeOut(200, function() {
                            $(this).remove();
                        });
                    });
                    
                    // Cerrar modal al hacer clic fuera del contenido
                    $('#result-details-modal').on('click', function(e) {
                        if ($(e.target).is('#result-details-modal')) {
                            $('#result-details-modal').fadeOut(200, function() {
                                $(this).remove();
                            });
                        }
                    });
                }
            });
        }
    };

    // Inicializar cuando el DOM esté listo
    $(function() {
        EnglishLineTest.ResultsManager.init();
    });

})(jQuery);