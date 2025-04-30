(function ($) {
    'use strict';

    window.EnglishLineTest = window.EnglishLineTest || {};

    EnglishLineTest.questionTypeLabels = {
        section: 'Nueva Sección',
        title: 'Título',
        paragraph: 'Párrafo',
        text: 'Texto corto',
        textarea: 'Texto largo',
        select: 'Desplegable',
        radio: 'Opción única',
        checkbox: 'Opción múltiple',
        image: 'Imagen con descripción',
        cloze: 'Completar texto',
        ordering: 'Ordenar elementos',
        'true-false': 'Verdadero/Falso'
    };

    EnglishLineTest.FormUI = {
        createSectionElement: function (section, index) {
            const questionTypeLabels = EnglishLineTest.questionTypeLabels;

            let $section = $('<div class="form-section"></div>')
                .attr('data-section-index', index)
                .html(`
                <div class="form-section-header">
                    <h3 class="form-section-title">${section.title || questionTypeLabels.section}</h3>
                    <div class="form-section-actions">
                        <button type="button" class="form-section-action" data-action="toggle">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                        <button type="button" class="form-section-action" data-action="edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="form-section-action delete" data-action="delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="form-section-content">
                    <div class="form-section-config" style="display: none;">
                        <div class="form-field">
                            <label>Título de la sección</label>
                            <input type="text" class="form-section-field" data-field="title" value="${section.title || ''}">
                        </div>
                        <div class="form-field">
                            <label>Descripción</label>
                            <textarea class="form-section-field" data-field="description" rows="3">${section.description || ''}</textarea>
                        </div>
                        <div class="form-field">
                            <label>Límite de tiempo (minutos)</label>
                            <input type="number" class="form-section-field" data-field="timeLimit" value="${section.timeLimit || section.time_limit || '0'}" min="0" step="1">
                            <p class="field-help">Dejar en 0 para no establecer límite de tiempo</p>
                        </div>
                    </div>
                    <div class="form-questions-container"></div>
                    <div class="form-questions-empty">
                        <p>Arrastra preguntas aquí para añadirlas a esta sección.</p>
                    </div>
                </div>
            `);

            return $section;
        },

        createOptionElement: function (option, index) {
            return $('<div class="form-option"></div>')
                .attr('data-option-index', index)
                .html(`
                <div class="option-container">
                    <input type="text" class="option-text" value="${option.text || ''}">
                    <label class="option-correct-label" title="Marcar como respuesta correcta (activa la autocalificación)">
                        <input type="checkbox" class="option-correct" ${option.correct ? 'checked' : ''}>
                        <span class="dashicons dashicons-yes-alt"></span>
                    </label>
                    <button type="button" class="remove-option-btn">×</button>
                </div>
            `);
        },

        createQuestionElement: function (question, index) {
            const questionTypeLabels = EnglishLineTest.questionTypeLabels;
            const type = question.type;

            let $question = $('<div class="form-question"></div>')
                .attr('data-question-index', index)
                .attr('data-question-type', type)
                .html(`
                <div class="form-question-header">
                    <span class="form-question-type">${questionTypeLabels[type] || type}</span>
                    <div class="form-question-actions">
                        <button type="button" class="form-question-action" data-action="edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="form-question-action delete" data-action="delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="form-question-content">
                    ${EnglishLineTest.FormUI.createQuestionContent(question)}
                </div>
                <div class="form-question-config" style="display: none;">
                    ${EnglishLineTest.FormUI.getQuestionConfigFields(question)}
                </div>
            `);
            
            return $question;
        },

        createQuestionContent: function(question) {
            const type = question.type;

            switch(type) {
                case 'title':
                    return `<div class="form-question-text title-preview size-${question.titleSize || question.title_size || 'h2'} align-${question.titleAlignment || question.title_alignment || 'center'}">${question.text || 'Título'}</div>`;

                case 'paragraph':
                    return `<div class="form-question-text paragraph-preview align-${question.paragraphAlignment || question.paragraph_alignment || 'left'}">${question.text || 'Párrafo informativo'}</div>`;

                case 'image':
                    let imageHtml = `<div class="form-question-text">${question.text || 'Describa la imagen'}</div>`;
                    if (question.imageId || question.image_id) {
                        let imageId = question.imageId || question.image_id;
                        let attachment = wp.media.attachment(imageId);
                        if (attachment && attachment.get('url')) {
                            imageHtml += `<div class="image-preview-container"><img src="${attachment.get('url')}" alt="Vista previa"></div>`;
                        } else {
                            imageHtml += '<div class="image-placeholder">Error al cargar la vista previa de la imagen</div>';
                        }
                    } else {
                        imageHtml += '<div class="image-placeholder">Imagen no seleccionada</div>';
                    }
                    return imageHtml;

                case 'cloze':
                    let clozeText = question.clozeText || question.cloze_text || 'Texto con [espacios] para completar';
                    let clozePreview = clozeText.replace(/\[(.*?)\]/g, '<span class="cloze-blank">[$1]</span>');
                    return `
                        <div class="form-question-text">${question.text || 'Completa el texto'}</div>
                        <div class="cloze-preview">${clozePreview}</div>
                    `;
                case 'ordering':
                    let orderingItemsText = question.itemsText || question.items_text || "Elemento 1\nElemento 2\nElemento 3";
                    let orderingItems = orderingItemsText.split('\n').filter(item => item.trim() !== '');
                    let orderingPreview = '<ol class="ordering-preview-list">';
                    orderingItems.forEach(item => {
                        orderingPreview += `<li>${item.trim()}</li>`;
                    });
                    orderingPreview += '</ol>';
                    return `
                        <div class="form-question-text">${question.text || 'Ordena los elementos'}</div>
                        ${orderingPreview}
                    `;
                case 'true-false':
                    return `
                        <div class="form-question-text">${question.statement || 'Afirmación Verdadero/Falso'}</div>
                        <div class="true-false-preview">
                            <span class="option">Verdadero</span> / <span class="option">Falso</span>
                        </div>
                    `;
                case 'text':
                case 'textarea':
                case 'select':
                case 'radio':
                case 'checkbox':
                default:
                    return `<div class="form-question-text">${question.text || 'Nueva Pregunta'}</div>`;
            }
        },

        getOptionsFieldsHTML: function(type) {
            let helpText = '';
            
            if (type === 'checkbox') {
                helpText = 'Marca los ✓ junto a las respuestas correctas para activar la autocalificación';
            } else {
                helpText = 'Marca el ✓ junto a la respuesta correcta para activar la autocalificación';
            }
            
            return `
                <div class="form-field">
                    <label>Opciones</label>
                    <div class="form-options-container"></div>
                    <button type="button" class="add-option-btn">+ Añadir opción</button>
                    <p class="field-help">${helpText}</p>
                    <input type="hidden" class="form-question-field" data-field="isGradable" value="true">
                </div>`;
        },

        getQuestionConfigFields: function(question) {
            const type = question.type;
            const questionTypeLabels = EnglishLineTest.questionTypeLabels;
            let specificFieldsHTML = '';

            const commonFieldsHTML = `
                <div class="form-field">
                    <label>${type === 'title' ? 'Texto del título' : type === 'paragraph' ? 'Título del párrafo' : 'Texto de la pregunta'}</label>
                    <input type="text" class="form-question-field" data-field="text" value="${question.text || ''}">
                </div>
                ${type !== 'title' && type !== 'paragraph' ? `
                    <div class="form-field">
                        <label>
                            <input type="checkbox" class="form-question-field" data-field="required" ${question.required ? 'checked' : ''}>
                            Obligatoria
                        </label>
                    </div>
                `: ''}
            `;

            const isGradableType = EnglishLineTest.FormData.gradableTypes.includes(type);
            const isOptionType = (type === 'select' || type === 'radio' || type === 'checkbox' || type === 'true-false');
            
            const gradingFieldsHTML = isGradableType && !isOptionType ? `
                <div class="form-field grading-config">
                    <h4 class="section-title">Configuración de autocalificación</h4>
                    <div class="form-field">
                        <label>
                            <input type="checkbox" class="form-question-field" data-field="isGradable" ${question.isGradable ? 'checked' : ''}>
                            Habilitar autocalificación para esta pregunta
                        </label>
                    </div>
                    <div class="grading-specific-fields" style="${question.isGradable ? '' : 'display: none;'}">
                        ${this.getGradingConfigFields(question)}
                    </div>
                </div>
            ` : '';
            
            const additionalCommonFields = `
                ${type !== 'title' && type !== 'paragraph' ? `
                    <div class="form-field">
                        <label>Pista o ayuda</label>
                        <input type="text" class="form-question-field" data-field="hint" value="${question.hint || ''}">
                    </div>
                    <div class="form-field">
                        <label>Explicación</label>
                        <textarea class="form-question-field" data-field="explanation" rows="3">${question.explanation || ''}</textarea>
                    </div>
                `: ''}
            `;

            switch (type) {
                case 'textarea':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Límite de caracteres</label>
                            <input type="number" class="form-question-field" data-field="maxChars" value="${question.maxChars || question.max_chars || '0'}" min="0">
                            <p class="field-help">Dejar en 0 para no establecer límite</p>
                        </div>`;
                    break;
                case 'select':
                case 'radio':
                case 'checkbox':
                    specificFieldsHTML = this.getOptionsFieldsHTML(type);
                    break;
                case 'title':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Tamaño del título</label>
                            <select class="form-question-field" data-field="titleSize">
                                <option value="h1" ${(question.titleSize || question.title_size) === 'h1' ? 'selected' : ''}>Grande</option>
                                <option value="h2" ${(question.titleSize || question.title_size) === 'h2' ? 'selected' : ''}>Mediano</option>
                                <option value="h3" ${(question.titleSize || question.title_size) === 'h3' ? 'selected' : ''}>Pequeño</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Alineación del título</label>
                            <select class="form-question-field" data-field="titleAlignment">
                                <option value="left" ${(question.titleAlignment || question.title_alignment) === 'left' ? 'selected' : ''}>Izquierda</option>
                                <option value="center" ${(question.titleAlignment || question.title_alignment) === 'center' ? 'selected' : ''}>Centro</option>
                                <option value="right" ${(question.titleAlignment || question.title_alignment) === 'right' ? 'selected' : ''}>Derecha</option>
                            </select>
                        </div>`;
                    break;
                case 'paragraph':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Alineación del párrafo</label>
                            <select class="form-question-field" data-field="paragraphAlignment">
                                <option value="left" ${(question.paragraphAlignment || question.paragraph_alignment) === 'left' ? 'selected' : ''}>Izquierda</option>
                                <option value="center" ${(question.paragraphAlignment || question.paragraph_alignment) === 'center' ? 'selected' : ''}>Centro</option>
                                <option value="right" ${(question.paragraphAlignment || question.paragraph_alignment) === 'right' ? 'selected' : ''}>Derecha</option>
                                <option value="justify" ${(question.paragraphAlignment || question.paragraph_alignment) === 'justify' ? 'selected' : ''}>Justificado</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Contenido del párrafo</label>
                            <textarea class="form-question-field" data-field="paragraphContent" rows="5">${question.paragraphContent || ''}</textarea>
                        </div>`;
                    break;
                case 'cloze':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Contenido del texto para completar</label>
                            <textarea class="form-question-field" data-field="clozeText" rows="5" placeholder="Escribe el texto con [palabras] para completar">${question.clozeText || question.cloze_text || ''}</textarea>
                            <p class="field-help">Usa corchetes [ ] para marcar las palabras que el usuario debe completar.</p>
                        </div>
                        <div class="form-field">
                            <label>
                                <input type="checkbox" class="form-question-field" data-field="caseSensitive" ${question.caseSensitive || question.case_sensitive ? 'checked' : ''}>
                                Sensible a mayúsculas y minúsculas
                            </label>
                        </div>`;
                    break;
                case 'true-false':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Afirmación</label>
                            <input type="text" class="form-question-field" data-field="statement" value="${question.statement || ''}">
                        </div>
                        <div class="form-field">
                            <label>Respuesta correcta</label>
                            <select class="form-question-field" data-field="correctValue">
                                <option value="true" ${question.correctValue === true ? 'selected' : ''}>Verdadero</option>
                                <option value="false" ${question.correctValue === false ? 'selected' : ''}>Falso</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Retroalimentación correcta (opcional)</label>
                            <textarea class="form-question-field" data-field="feedbackCorrect" rows="2">${question.feedbackCorrect || question.feedback_correct || ''}</textarea>
                        </div>
                        <div class="form-field">
                            <label>Retroalimentación incorrecta (opcional)</label>
                            <textarea class="form-question-field" data-field="feedbackIncorrect" rows="2">${question.feedbackIncorrect || question.feedback_incorrect || ''}</textarea>
                        </div>`;
                    break;
                case 'image':
                    let imagePreview = '';
                    let removeButtonStyle = 'display:none;';
                    const imageId = question.imageId || question.image_id;
                    
                    if (imageId) {
                        try {
                            const imageUrl = wp.media.attachment(imageId).get('url');
                            if (imageUrl) {
                                imagePreview = `<img src="${imageUrl}" style="max-width:100px;max-height:100px;">`;
                                removeButtonStyle = '';
                            } else {
                                imagePreview = '<div class="no-image">Cargando imagen...</div>';
                            }
                        } catch (e) {
                            imagePreview = '<div class="no-image">Error al cargar la imagen</div>';
                        }
                    } else {
                        imagePreview = '<div class="no-image">Sin imagen</div>';
                    }
                    
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Imagen</label>
                            <div class="image-selector">
                                <div class="image-preview">
                                    ${imagePreview}
                                </div>
                                <button type="button" class="button button-secondary select-image-btn">Seleccionar imagen</button>
                                <input type="hidden" class="form-question-field image-id-field" 
                                       data-field="imageId" value="${imageId || ''}">
                                <button type="button" class="button button-link remove-image-btn" 
                                       style="color:red;${removeButtonStyle}">Eliminar imagen</button>
                            </div>
                        </div>
                        <div class="form-field">
                            <label>Pregunta sobre la imagen</label>
                            <input type="text" class="form-question-field" data-field="imageQuestionText" 
                                  value="${question.imageQuestionText || question.image_question_text || ''}">
                        </div>
                        <div class="form-field">
                            <label>Límite de caracteres para la descripción (opcional)</label>
                            <input type="number" class="form-question-field" data-field="imageMaxChars" 
                                  value="${question.imageMaxChars || question.image_max_chars || '500'}" min="0">
                            <p class="field-help">Dejar en 0 para sin límite</p>
                        </div>`;
                    break;
                case 'ordering':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Elementos para ordenar</label>
                            <textarea class="form-question-field" data-field="itemsText" rows="5" placeholder="Introduce cada elemento en una línea">${question.itemsText || question.items_text || ''}</textarea>
                            <p class="field-help">Introduce los elementos en el orden correcto, uno por línea. Se mostrarán desordenados al usuario.</p>
                        </div>`;
                    break;
            }

            return commonFieldsHTML + gradingFieldsHTML + additionalCommonFields + specificFieldsHTML;
        },

        getGradingConfigFields: function(question) {
            const type = question.type;
            
            switch (type) {
                case 'text':
                case 'textarea':
                    return `
                        <div class="form-field">
                            <label>Respuesta correcta</label>
                            <textarea class="form-question-field" data-field="correctAnswer" rows="3">${question.correctAnswer || ''}</textarea>
                            <p class="field-help">Introduce la respuesta que será considerada correcta.</p>
                        </div>
                        <div class="form-field">
                            <label>
                                <input type="checkbox" class="form-question-field" data-field="caseSensitive" ${question.caseSensitive || question.case_sensitive ? 'checked' : ''}>
                                Sensible a mayúsculas/minúsculas
                            </label>
                        </div>
                    `;
                
                case 'cloze':
                    return `
                        <div class="form-field">
                            <label>Configuración para autocalificación</label>
                            <p class="field-help">Las respuestas correctas son las que están entre corchetes en el texto.</p>
                        </div>
                    `;
                
                case 'ordering':
                    return `
                        <div class="form-field">
                            <label>Para calificación automática</label>
                            <p class="field-help">El orden correcto se determina por el orden en que introduces los elementos en el campo de texto.</p>
                        </div>
                    `;
                
                default:
                    return '';
            }
        },

        toggleSectionEdit: function ($section) {
            let $config = $section.find('.form-section-config');

            $config.slideToggle(200);
            $section.toggleClass('editing');

            if ($section.hasClass('editing')) {
                $section.find('.form-section-field[data-field="title"]').focus();
            }
        },

        toggleSectionContent: function ($section) {
            let $content = $section.find('.form-section-content');
            let $toggleBtn = $section.find('.form-section-action[data-action="toggle"] .dashicons');

            $content.slideToggle(200);
            $section.toggleClass('collapsed');

            if ($section.hasClass('collapsed')) {
                $toggleBtn.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            } else {
                $toggleBtn.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            }
        },

        toggleQuestionEdit: function ($question) {
            let $config = $question.find('.form-question-config');

            $config.slideToggle(200);
            $question.toggleClass('editing');

            if ($question.hasClass('editing')) {
                $question.find('.form-question-field[data-field="text"]').focus();
            }
        }
    };

})(jQuery);