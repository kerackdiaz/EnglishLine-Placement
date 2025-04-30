(function ($) {
  'use strict';

  window.EnglishLineTest = window.EnglishLineTest || {};

  EnglishLineTest.FormUtils = {
    getDefaultQuestionTitle: function(type) {
      const labels = {
        'text': 'Pregunta de texto corto',
        'textarea': 'Pregunta de texto largo',
        'select': 'Pregunta de selección',
        'radio': 'Pregunta de opción única',
        'checkbox': 'Pregunta de selección múltiple',
        'title': 'Título de sección',
        'paragraph': 'Texto explicativo',
        'cloze': 'Ejercicio de completar huecos',
        'ordering': 'Ejercicio de ordenar elementos',
        'image': 'Descripción de imagen',
        'true-false': 'Pregunta de verdadero/falso'
      };
      
      return labels[type] || 'Nueva pregunta';
    },

    updateSectionIndices: function() {
      let self = this;
      let newSectionsData = [];
      
      this.$sectionsContainer.find('.form-section').each(function(index) {
        let oldIndex = parseInt($(this).data('section-index'));
        
        $(this).attr('data-section-index', index);
        
        if (self.sectionsData[oldIndex]) {
          newSectionsData[index] = self.sectionsData[oldIndex];
        }
      });
      
      this.sectionsData = newSectionsData;
      EnglishLineTest.FormData.saveFormData();
    },
    
    updateQuestionIndices: function(sectionIndex) {
      let self = this;
      let newQuestionsData = [];
      let $section = this.$sectionsContainer.find('.form-section[data-section-index="' + sectionIndex + '"]');
      
      $section.find('.form-question').each(function(index) {
        let oldIndex = parseInt($(this).data('question-index'));
        
        $(this).attr('data-question-index', index);
        
        if (self.sectionsData[sectionIndex] && 
            self.sectionsData[sectionIndex].questions && 
            self.sectionsData[sectionIndex].questions[oldIndex]) {
          newQuestionsData[index] = self.sectionsData[sectionIndex].questions[oldIndex];
        }
      });
      
      if (this.sectionsData[sectionIndex]) {
        this.sectionsData[sectionIndex].questions = newQuestionsData;
      }
      EnglishLineTest.FormData.saveFormData();
    },
    
    reindexOptions: function(sectionIndex, questionIndex) {
      let self = this;
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (!question || !question.options) return;
      
      let $section = this.$sectionsContainer.find('.form-section[data-section-index="' + sectionIndex + '"]');
      let $question = $section.find('.form-question[data-question-index="' + questionIndex + '"]');
      
      question.options.forEach((option, idx) => {
        option.text = "Opción " + (idx + 1);
      });
      
      $question.find('.form-option').each(function(idx) {
        $(this).attr('data-option-index', idx);
        $(this).find('.option-text').val("Opción " + (idx + 1));
      });
    }
  };
})(jQuery);