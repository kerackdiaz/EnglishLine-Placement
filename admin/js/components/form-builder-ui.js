(function ($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    // **Definición ACTUALIZADA de questionTypeLabels SIN los componentes eliminados**
    EnglishLineTest.questionTypeLabels = {
        section: 'Nueva Sección',
        title: 'Título',
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
        /**
         * @param {Object} section - Datos de la sección
         * @param {number} index - Índice de la sección
         * @return {jQuery} Elemento de sección
         */
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
                            <input type="number" class="form-section-field" data-field="time_limit" value="${section.time_limit || '0'}" min="0" step="1">
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
        /**
         *
         * @param {Object} option - Datos de la opción
         * @param {number} index - Índice de la opción
         * @return {jQuery} Elemento de opción
         */
        createOptionElement: function (option, index) {
            return $('<div class="form-option"></div>')
                .attr('data-option-index', index)
                .html(`
                <input type="text" class="option-text" value="${option.text || ''}">
                <button type="button" class="remove-option-btn">×</button>
            `);
        },
        /**
         *
         * @param {Object} question - Datos de la pregunta
         * @param {number} index - Índice de la pregunta
         * @return {jQuery} Elemento de pregunta
         */
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

        /**
         * @param {Object} question - Datos de la pregunta
         * @return {string} HTML del contenido de la pregunta
         */
        createQuestionContent: function(question) {
            const type = question.type;

            switch(type) {
                case 'title':
                    return `<div class="form-question-text title-preview size-${question.title_size || 'h2'} align-${question.title_alignment || 'center'}">${question.text || 'Título'}</div>`;

                case 'image':
                    let imageHtml = `<div class="form-question-text">${question.text || 'Describa la imagen'}</div>`;
                    if (question.image_id) {
                        let attachment = wp.media.attachment(question.image_id);
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
                    let clozeText = question.cloze_text || 'Texto con [espacios] para completar';
                    let clozePreview = clozeText.replace(/\[(.*?)\]/g, '<span class="cloze-blank">[$1]</span>');
                    return `
                        <div class="form-question-text">${question.text || 'Completa el texto'}</div>
                        <div class="cloze-preview">${clozePreview}</div>
                    `;
                case 'ordering':
                    let orderingItemsText = question.items_text || "Elemento 1\nElemento 2\nElemento 3";
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

        /**
         * Obtiene el HTML para los campos de configuración de un tipo de pregunta específico
         * @param {Object} question - Datos de la pregunta
         * @return {string} HTML string con los campos de configuración
         */
        getQuestionConfigFields: function(question) {
            const type = question.type;
            const questionTypeLabels = EnglishLineTest.questionTypeLabels;
            let specificFieldsHTML = '';

            //Campos comunes a todos los tipos de pregunta
            const commonFieldsHTML = `
                <div class="form-field">
                    <label>Tipo de pregunta</label>
                    <select class="question-type-select form-question-field" data-field="type">
                        <option value="text" ${type === 'text' ? 'selected' : ''}>${questionTypeLabels.text}</option>
                        <option value="textarea" ${type === 'textarea' ? 'selected' : ''}>${questionTypeLabels.textarea}</option>
                        <option value="select" ${type === 'select' ? 'selected' : ''}>${questionTypeLabels.select}</option>
                        <option value="radio" ${type === 'radio' ? 'selected' : ''}>${questionTypeLabels.radio}</option>
                        <option value="checkbox" ${type === 'checkbox' ? 'selected' : ''}>${questionTypeLabels.checkbox}</option>
                        <option value="title" ${type === 'title' ? 'selected' : ''}>${questionTypeLabels.title}</option>
                        <option value="image" ${type === 'image' ? 'selected' : ''}>${questionTypeLabels.image}</option>
                        <option value="cloze" ${type === 'cloze' ? 'selected' : ''}>${questionTypeLabels.cloze}</option>
                        <option value="ordering" ${type === 'ordering' ? 'selected' : ''}>${questionTypeLabels.ordering}</option>
                        <option value="true-false" ${type === 'true-false' ? 'selected' : ''}>${questionTypeLabels['true-false']}</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>${type === 'title' ? 'Texto del título' : 'Texto de la pregunta'}</label>
                    <input type="text" class="form-question-field" data-field="text" value="${question.text || ''}">
                </div>
                ${type !== 'title' ? `
                    <div class="form-field">
                        <label>
                            <input type="checkbox" class="form-question-field" data-field="required" ${question.required ? 'checked' : ''}>
                            Obligatoria
                        </label>
                    </div>
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
                            <input type="number" class="form-question-field" data-field="max_chars" value="${question.max_chars || '0'}" min="0">
                            <p class="field-help">Dejar en 0 para no establecer límite</p>
                        </div>`;
                    break;
                case 'select':
                case 'radio':
                case 'checkbox':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Opciones</label>
                            <div class="form-options-container"></div>
                            <button type="button" class="add-option-btn">+ Añadir opción</button>
                        </div>`;
                    break;
                case 'title':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Tamaño del título</label>
                            <select class="form-question-field" data-field="title_size">
                                <option value="h1" ${question.title_size === 'h1' ? 'selected' : ''}>Grande</option>
                                <option value="h2" ${question.title_size === 'h2' ? 'selected' : ''}>Mediano</option>
                                <option value="h3" ${question.title_size === 'h3' ? 'selected' : ''}>Pequeño</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Alineación del título</label>
                            <select class="form-question-field" data-field="title_alignment">
                                <option value="left" ${question.title_alignment === 'left' ? 'selected' : ''}>Izquierda</option>
                                <option value="center" ${question.title_alignment === 'center' ? 'selected' : ''}>Centro</option>
                                <option value="right" ${question.title_alignment === 'right' ? 'selected' : ''}>Derecha</option>
                            </select>
                        </div>`;
                    break;
                case 'cloze':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Contenido del texto para completar</label>
                            <textarea class="form-question-field" data-field="cloze_text" rows="5" placeholder="Escribe el texto con [palabras] para completar">${question.cloze_text || ''}</textarea>
                            <p class="field-help">Usa corchetes [ ] para marcar las palabras que el usuario debe completar.</p>
                        </div>
                        <div class="form-field">
                            <label>
                                <input type="checkbox" class="form-question-field" data-field="case_sensitive" ${question.case_sensitive ? 'checked' : ''}>
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
                            <select class="form-question-field" data-field="correct_answer">
                                <option value="true" ${question.correct_answer === true ? 'selected' : ''}>Verdadero</option>
                                <option value="false" ${question.correct_answer === false ? 'selected' : ''}>Falso</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Retroalimentación correcta (opcional)</label>
                            <textarea class="form-question-field" data-field="feedback_correct" rows="2">${question.feedback_correct || ''}</textarea>
                        </div>
                        <div class="form-field">
                            <label>Retroalimentación incorrecta (opcional)</label>
                            <textarea class="form-question-field" data-field="feedback_incorrect" rows="2">${question.feedback_incorrect || ''}</textarea>
                        </div>`;
                    break;
                    case 'image':
                        let imagePreview = '';
                        let removeButtonStyle = 'display:none;';
                        
                        if (question.image_id) {
                            try {
                                const imageUrl = wp.media.attachment(question.image_id).get('url');
                                if (imageUrl) {
                                    imagePreview = `<img src="${imageUrl}" style="max-width:100px;max-height:100px;">`;
                                    removeButtonStyle = '';
                                } else {
                                    imagePreview = '<div class="no-image">Cargando imagen...</div>';

                                    wp.media.attachment(question.image_id).fetch().done(function(data, questionIndex) { 
                                        const url = data.url;
                                        const $preview = $(`.form-question[data-question-index="${questionIndex}"] .image-preview`);
                                        $preview.html(`<img src="${url}" style="max-width:100px;max-height:100px;">`);
                                        $(`.form-question[data-question-index="${questionIndex}"] .remove-image-btn`).show();
                                    }.bind(null, questionIndex)).fail(function() { 
                                        const $preview = $(`.form-question[data-question-index="${questionIndex}"] .image-preview`);
                                        $preview.html('<div class="no-image">Error al cargar la imagen</div>');
                                    });
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
                                           data-field="image_id" value="${question.image_id || ''}">
                                    <button type="button" class="button button-link remove-image-btn" 
                                           style="color:red;${removeButtonStyle}">Eliminar imagen</button>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Pregunta sobre la imagen</label>
                                <input type="text" class="form-question-field" data-field="image_question_text" 
                                      value="${question.image_question_text || ''}">
                            </div>
                            <div class="form-field">
                                <label>Límite de caracteres para la descripción (opcional)</label>
                                <input type="number" class="form-question-field" data-field="image_max_chars" 
                                      value="${question.image_max_chars || '500'}" min="0">
                                <p class="field-help">Dejar en 0 para sin límite</p>
                            </div>`;
                        break;
                case 'ordering':
                    specificFieldsHTML = `
                        <div class="form-field">
                            <label>Elementos para ordenar</label>
                            <textarea class="form-question-field" data-field="items_text" rows="5" placeholder="Introduce cada elemento en una línea">${question.items_text || ''}</textarea>
                            <p class="field-help">Introduce los elementos en el orden correcto, uno por línea. Se mostrarán desordenados al usuario.</p>
                        </div>`;
                    break;
            }

            return commonFieldsHTML + specificFieldsHTML;
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
        },
    };
})(jQuery);