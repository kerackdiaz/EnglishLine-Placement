(function ($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    EnglishLineTest.FormDragAndDrop = {
        /**
         * Inicializa el sistema de arrastrar y soltar
         */
        initSortable: function () {
            let self = this;

            // Hacer arrastrable los componentes de la barra lateral
            self.$formComponents.draggable({
                helper: 'clone',
                revert: 'invalid',
                start: function (event, ui) {
                    self.draggedComponent = $(this).data('type');
                    self.$sectionsContainer.addClass('drag-highlight');
                },
                stop: function () {
                    self.draggedComponent = null;
                    self.$sectionsContainer.removeClass('drag-highlight');
                }
            });

            // Contenedor de secciones sortable y droppable SOLO para componentes de SECCIÓN
            self.$sectionsContainer.sortable({
                items: '.form-section',
                handle: '.form-section-header',
                placeholder: 'form-section-placeholder',
                tolerance: 'pointer',
                opacity: 0.7,
                update: function () {
                    EnglishLineTest.FormUtils.updateSectionIndices.call(self);
                }
            }).droppable({
                accept: '.form-component[data-type="section"]',
                tolerance: 'pointer',
                hoverClass: 'droppable-active',
                drop: function (event, ui) {
                    if ($(ui.draggable).data('type') === 'section') {
                        EnglishLineTest.FormData.addSection.call(self);
                    }
                }
            });

            // Configuración para hacer droppable los contenedores de preguntas
            $(document).on('mouseover', '.form-questions-container', function () {
                let $container = $(this);
                if (!$container.hasClass('droppable-initialized')) {
                    $container.droppable({
                        accept: '.form-component:not([data-type="section"])',
                        tolerance: 'pointer',
                        hoverClass: 'droppable-active',
                        drop: function (event, ui) {
                            console.log("DROP EVENT DETECTED!");
                            let type = $(ui.draggable).data('type');
                            let $section = $(this).closest('.form-section');
                            let sectionIndex = $section.data('section-index');

                            if (type !== 'section') {
                                console.log("Adding question of type:", type, "to section:", sectionIndex);
                                EnglishLineTest.FormData.addQuestion.call(self, sectionIndex, type);
                            }
                        }
                    }).addClass('droppable-initialized');
                }
            });
        },
    };
})(jQuery);