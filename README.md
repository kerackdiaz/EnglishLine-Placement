# EnglishLine Placement

## Descripción
**EnglishLine Placement** es un potente plugin para WordPress que permite crear y gestionar exámenes de inglés online. Con este plugin podrás evaluar el nivel de inglés de los estudiantes mediante diversos tipos de preguntas, calificar automáticamente las respuestas y enviar certificados personalizados a los participantes.

## Requisitos
- WordPress 5.6 o superior
- PHP 7.2 o superior
- MySQL 5.6 o superior
- Configuración de correo electrónico activa en WordPress

## Instalación
1. Sube la carpeta `englishline-placement` al directorio `plugins` de tu WordPress.
2. Activa el plugin desde el menú 'Plugins' en WordPress.
3. Ve a **"EnglishLine Placement > Configuración"** para establecer las opciones básicas.

## Características principales
- **Diversos tipos de preguntas:** Respuesta corta, selección múltiple, verdadero/falso, completar huecos, etc.
- **Calificación automática y manual:** Califica respuestas objetivas automáticamente y permite revisión manual de preguntas abiertas.
- **Envío de resultados por email:** Notifica automáticamente a los participantes sobre sus calificaciones.
- **Panel de administración intuitivo:** Gestiona fácilmente todos tus exámenes desde un solo lugar.

## Primeros pasos

### Crear tu primer examen
1. Ve a **"EnglishLine Placement > Formularios"** y haz clic en **"Añadir nuevo"**.
2. Dale un título al examen y comienza a añadir secciones y preguntas.
3. Configura el tiempo límite, opciones de calificación y otros ajustes.
4. Guarda el formulario para generar un **shortcode**.

### Publicar un examen en tu web
1. Crea una nueva página o entrada.
2. Inserta el shortcode generado:  
   ```
   [englishline_form id="1"]
   ```
3. Publica la página y ¡listo!

### Revisar resultados
1. Ve a **"EnglishLine Placement > Resultados"**.
2. Filtra por fecha, formulario o estado.
3. Haz clic en cualquier resultado para ver los detalles y calificar preguntas subjetivas.

## Configuración avanzada

### Opciones de envío de correo
1. Ve a **"EnglishLine Placement > Configuración > Correo electrónico"**.
2. Personaliza las plantillas de correo para notificaciones.
3. Configura el remitente y los destinatarios de las notificaciones.

## Desinstalación
El plugin incluye una opción para eliminar todos los datos cuando se desinstala:

1. Ve a **"EnglishLine Placement > Configuración > General"**.
2. Marca/desmarca la opción **"Eliminar todos los datos al desinstalar el plugin"**.
3. Si esta opción está habilitada, todos los datos (formularios, resultados, configuraciones) se eliminarán al desinstalar.

## Preguntas frecuentes

**¿Cómo puedo hacer que algunas preguntas no cuenten para la calificación final?**  
Al revisar un resultado, puedes desmarcar la casilla **"Incluir en calificación"** junto a cualquier pregunta.

**¿Se pueden establecer diferentes puntajes para cada pregunta?**  
Sí, puedes asignar calificaciones específicas para cada pregunta durante la revisión.

**¿Es posible limitar el tiempo de los exámenes?**  
Sí, puedes establecer un tiempo límite para cada formulario en la configuración del mismo.

## Soporte
Si necesitas ayuda con el plugin, puedes:

- Consultar la documentación completa en: [https://cocreadores.co](https://cocreadores.co)
- Contactar con soporte en: [cocreadores.sistemas@gmail.com](mailto:cocreadores.sistemas@gmail.com)

## Licencia
Este plugin está licenciado bajo la **Licencia GPL v2**.

© 2025 EnglishLine Test. Todos los derechos reservados.
