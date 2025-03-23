(function ($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    $(document).ready(function () {
        EnglishLineTest.FormBuilder.init();
    });

    /**
     * Módulo del constructor de formularios
     */
    EnglishLineTest.FormBuilder = {

        // Elementos DOM
        $formBuilder: null,
        $titleField: null,
        $descriptionField: null,
        $formComponents: null,
        $sectionsContainer: null,
        $saveFormBtn: null,

        // Datos del formulario
        formId: 0,
        sectionsData: [],
        currentSectionIndex: 0,
        draggedComponent: null,

        /**
         * Inicializa el constructor de formularios
         */
        init: function () {
            this.cacheElements();
            if (this.$formBuilder.length) {
                EnglishLineTest.FormData.setupFormData.call(this);
                EnglishLineTest.FormEvents.bindEvents.call(this);
                EnglishLineTest.FormDragAndDrop.initSortable.call(this);
                EnglishLineTest.FormData.loadExistingFormData.call(this);
            }
        },

        /**
         * Almacena referencias a los elementos DOM
         */
        cacheElements: function () {
            this.$formBuilder = $('#form-builder');
            this.$titleField = $('#form-title');
            this.$descriptionField = $('#form-description');
            this.$formComponents = $('.form-component');
            this.$sectionsContainer = $('#form-sections-container');
            this.$saveFormBtn = $('#save-form-btn');
        },

        /**
         * Añade una pregunta a la UI en una sección específica
         *
         * @param {number} sectionIndex Índice de la sección
         * @param {object} question Datos de la pregunta
         * @param {number} index Índice de la pregunta dentro de la sección
         */
        addQuestionToUI: function (sectionIndex, question, index) {
            let $section = this.$sectionsContainer.find('.form-section[data-section-index="' + sectionIndex + '"]');
            let $questionsContainer = $section.find('.form-questions-container');
            let $newQuestion = EnglishLineTest.FormUI.createQuestionElement.call(this, question, index);

            // Añadir a la UI
            $questionsContainer.append($newQuestion);

            // Si es un tipo con opciones, añadir las opciones existentes
            if (['select', 'radio', 'checkbox'].includes(question.type) && question.options) {
                let $optionsContainer = $newQuestion.find('.form-options-container');
                let self = this;

                $.each(question.options, function (optIndex, option) {
                    let $option = EnglishLineTest.FormUI.createOptionElement.call(this, option, optIndex);
                    $optionsContainer.append($option);
                });
            }
        },

        /**
         * Añade una sección a la UI
         *
         * @param {object} section Datos de la sección
         * @param {number} index Índice de la sección
         */
        addSectionToUI: function (section, index) {
            let self = this;
            let sectionIndex = (typeof index !== 'undefined') ? index : this.sectionsData.length - 1;
            let $newSection = EnglishLineTest.FormUI.createSectionElement.call(this, section, sectionIndex);

            // Añadir a la UI
            this.$sectionsContainer.append($newSection);

            let $questionsContainer = $newSection.find('.form-questions-container');

            // Inicializar sortable SOLO para ORDENAR preguntas existentes
            $questionsContainer.sortable({
                items: '.form-question',
                handle: '.form-question-header',
                placeholder: 'form-question-placeholder',
                tolerance: 'pointer',
                opacity: 0.7,
                update: function () {
                    let $section = $(this).closest('.form-section');
                    let sectionIndex = $section.data('section-index');
                    EnglishLineTest.FormUtils.updateQuestionIndices.call(self, sectionIndex);
                }
            }).addClass('ui-sortable');

            // Hacer droppable explícitamente
            $questionsContainer.droppable({
                accept: '.form-component:not([data-type="section"])',
                tolerance: 'pointer',
                hoverClass: 'droppable-active',
                drop: function (event, ui) {
                    let type = $(ui.draggable).data('type');
                    if (type !== 'section') {
                        EnglishLineTest.FormData.addQuestion.call(self, sectionIndex, type);
                    }
                }
            }).addClass('droppable-initialized');

            // Añadir preguntas existentes si las hay
            if (section.questions && section.questions.length) {
                $.each(section.questions, function (qIndex, question) {
                    self.addQuestionToUI(sectionIndex, question, qIndex);
                });
            }
        },
    };
})(jQuery);