(function ($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    EnglishLineTest.FormUtils = {
        /**
         * Reindexar las opciones de una pregunta
         *
         * @param {number} sectionIndex - Índice de la sección
         * @param {number} questionIndex - Índice de la pregunta
         */
        reindexOptions: function (sectionIndex, questionIndex) {
            let $section = this.$sectionsContainer.find('.form-section[data-section-index="' + sectionIndex + '"]');
            let $question = $section.find('.form-question[data-question-index="' + questionIndex + '"]');

            $question.find('.form-option').each(function (index) {
                $(this).attr('data-option-index', index);
            });
        },

        /**
         * Actualiza los índices de las secciones después de reordenar o eliminar
         */
        updateSectionIndices: function () {
            let self = this;
            let newSectionsData = [];

            this.$sectionsContainer.find('.form-section').each(function (index) {
                $(this).attr('data-section-index', index);
                newSectionsData.push(self.sectionsData[$(this).data('section-index')]);
            });
            this.sectionsData = newSectionsData;
        },

        /**
         * Actualiza los índices de las preguntas después de reordenar o eliminar
         *
         * @param {number} sectionIndex - Índice de la sección
         */
        updateQuestionIndices: function (sectionIndex) {
            let self = this;
            let $section = this.$sectionsContainer.find('.form-section[data-section-index="' + sectionIndex + '"]');
            let newQuestions = [];

            $section.find('.form-question').each(function (index) {
                $(this).attr('data-question-index', index);
                newQuestions.push(self.sectionsData[sectionIndex].questions[$(this).data('question-index')]);
            });
            this.sectionsData[sectionIndex].questions = newQuestions;
        },

        /**
         * Obtiene un título predeterminado para un tipo de pregunta
         *
         * @param {string} type - Tipo de pregunta
         * @return {string} Título predeterminado
         */
        getDefaultQuestionTitle: function (type) {
            let titles = {
                'text': 'Pregunta de texto corto',
                'textarea': 'Pregunta de texto largo',
                'select': 'Pregunta de selección',
                'radio': 'Pregunta de opción única',
                'checkbox': 'Pregunta de opción múltiple',
                'file': 'Subida de archivo',
                'title': 'Título',
                'image': 'Describa la imagen',
                'cloze': 'Complete el texto',
                'drag-drop': 'Arrastre los elementos a su lugar correcto',
                'ordering': 'Ordene los elementos correctamente',
                'matching': 'Empareje los elementos de ambas columnas',
                'true-false': 'Indique si la afirmación es verdadera o falsa'
            };

            return titles[type] || 'Nueva pregunta';
        }
    };
})(jQuery);