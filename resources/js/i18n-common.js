export default {
    'en': {
        'fuelTypes': {
            'electric': 'electric',
            'natural_gas': 'natural gas',
            'dual': 'dual',
        },
        'currency': {
            'cents': 'cents',
            'dollars': 'dollars',
        },
        'uom': {
            'therm': 'therm',
            'kwh': 'kwh',
            'unknown': 'unknown',
            'ccf': 'ccf',
            'mwhs': 'mwhs',
            'gj': 'gj',
        },
        'identTypes': {
            'Account_Number': 'Account Number',
            'account_name': 'Account name',
            'Service_Agreement_ID': 'Service Agreement ID',
            'Customer_Number': 'Customer Number',
            'Pod_ID': 'Pod ID',
            'Service_Delivery_Identifier': 'Service Delivery Identifier',
            'Service_Number': 'Service Number',
            'Meter_Number': 'Meter Number',
            'Service_Reference_Number': 'Service Reference Number',
            'Name_Key': 'Name Key',
            'Billing_Account_Number': 'Billing Account Number',
            'Service_Point_ID': 'Service Point ID',
            'Site_ID': 'Site ID',
            'Choice_ID': 'Choice ID',
            'confirmation_code': 'Confirmation Code',
            'vendor_name': 'Vendor Name',
            'first_name': 'First Name',
            'last_name': 'Last Name',
            'agency': 'Agency',
            'agent_vendor': 'Agent Vendor',
            'agent_name': 'Agent Name',
            'sales_agent': 'Sales Agent',
            'email_address': 'Email Address',
            'billing_address': 'Billing Address',
            'service_address': 'Service Address',
            'agent': 'Agent',
            'type': 'Type',
            'ip_address': 'IP Address',
            'vendors': 'Vendors',
            'username': 'Username',
            'phone': 'Phone',
            'phone_number': 'Phone Number',
        },
        'dialogs': {
            'end_call_with_disposition': 'Thank You, we will mark this call as {disposition}. If you have any questions please call customer service at {cx_service_number}.',
            'end_call_no_disposition': 'Thank You for your time, we will mark your account(s) as not enrolled at this time. If you have any questions please call customer service at {cx_service_number}',
            'btn_is_sales_agent': 'The provided billing telephone number (BTN) is listed as a sales agent phone line, please provide an alternate number or contact your supervisor for assistance.',
            'acct_prev_enrolled': 'The account identifier(s) {identifier} has been previously enrolled. Please enter a valid account identifier or contact your supervisor for assistance.',
            'btn_reuse': 'This billing telephone number has been used to verify multiple accounts recently. Please check number entry, provide an alternate number, or contact your supervisor for assistance.',
            'svc_addr_reuse': 'This service address has been used for another sale. Please provide a valid service address or contact your supervisor for assistance.',
            'cust_prev_enrolled': 'Our records indicate the authorizing name and billing telephone number have been previously enrolled. Please enter a valid name and BTN or contact your supervisor for assistance.',
            'existing_acct': 'Our records indicate the authorizing name and billing telephone number have been previously enrolled. Please enter a valid name and BTN or contact your supervisor for assistance.',
            'voip': 'It appears that the Vendor Sales Agent is using a VOIP phone',
            'birthday_1': 'I apologize, the birthdate you provided is less than 18 years old.',
            'birthday_2': 'Please enter the birthday as MMDDYYYY',
            'sales_limit': 'I apologize agent, you have reached your sales limit for the day. If you have any questions please contact your supervisor because we will not be able to proceed. Thanks, have a great day!',
            'after_curfew': 'I apologize agent, you are not allowed to perform sales past the curfew time. If you have any questions please contact your supervisor. Thanks, have a great day!',
            'multi_tpv': 'Our records indicate the authorizing name and billing telephone number have been previously enrolled. Please enter a valid name and BTN or contact your supervisor for assistance.',
            'account_number_no_sale': 'We show this account has been verified too many times. Please have the customer call {brand} Customer Service {cx_service_number} to discuss their account options.',
            'btn_no_sale_dispositions': 'I\'m sorry.  We cannot enroll your account at this time due to multiple attempts on the premise.  If you have any questions, please contact {brand} at {cx_service_number}.',
            'account_number_good_sale': 'We show this account has been verified too many times. Please have the customer call {brand} at {cx_service_number} to discuss their account options.',
            'btn_no_sale': 'Our records indicate this billing telephone number is associated with a previous no sale. Please enter a valid BTN or contact your supervisor for assistance.',
            'email_reuse': 'This Email address has been used previously. Please provide an alternate email address or contact your supervisor for assistance.',
            'record_id_not_found': 'I\'m sorry, this Record ID could not be located.  We can attempt the import again or we can proceed manually.',
            'record_id_existing': 'I\'m sorry, this confirmation code has been used for a previous good sale please enter a valid confirmation number or we can proceed manually.',
            'record_id_wrong_state': 'I\'m sorry, this confirmation code is only valid in {needstate} but you called to verify for {instate}.',
            'indra_active_api_fail': 'Indra Energy has determined this customer is not eligible, do not proceed.',
        },
        'ui': {
            'date': 'Date',
            'yes': 'Yes',
            'no': 'No',
            'back': 'Back',
            'disable': 'Disable',
            'enable': 'Enable',
            'view': 'View',
            'edit': 'Edit',
            'delete': 'Delete',
            'update': 'Update',
            'filter': 'Filter',
            'submit': 'Submit',
            'clear': 'Clear',
            'submit': 'Submit',
            'close': 'Close',
            'welcome': 'Welcome, {name}!',
            'cancel': 'Cancel',
            'save': 'Save',
            'continue': 'Continue',
            'upload': 'Upload',
            'import': 'Import',
            'first_name': 'First Name',
            'last_name': 'Last Name',
            'm_i': 'Middle Initial',
            'optional': 'optional',
            'thankyou': 'Thank You',
            'goodbye': 'Goodbye',
            'agent': 'Agent',
            'end_call': 'End Call',
            'leave_voicemail': 'Leave Voicemail',
            'leave_voicemail_exit_reason': 'Hello, I am calling on behalf of {{client.name}}’s Sales Quality Assurance Team. I am sorry we missed you. We will attempt to contact you at a more convenient time. Thank you and have a great day!',
            'survey_feedback': 'Survey Feedback',
            'change_agent': 'Change Agent',
            'call_review': 'Call Review',
            'loading': 'Loading',
            'required': 'Required',
            'search': 'Search',
            'sale': 'Sale',
            'good_sale': 'Good Sale',
            'no_sale': 'No Sale',
            'good_sales': 'Good Sales',
            'no_sales': 'No Sales',
            'total_sales': 'Total Sales',
            'close_sale': 'Close Call',
            'choose': 'Choose',
            'finish': 'Finish',
            'address': 'Address',
            'zip_code': 'Zip Code',
            'postal_code': 'Postal Code',
            'city': 'City',
            'state': 'State',
            'a_and_b': '{a} and {b}',
            'province': 'Province',
            'vendor': 'Vendor',
            'vendors': 'Vendors',
            'brand': 'Brand',
            'office': 'Office',
            'commodity': 'Commodity',
            'market': 'Market',
            'channel': 'Channel',
            'channels': 'Channels',
            'language': 'Language',
            'result': 'Result',
            'product': 'Product',
            'bill_name': 'Bill Name',
            'confirmation': 'Confirmation',
            'disposition': 'Disposition',
            'home': 'Home',
            'reports': 'Reports',
            'data_export': 'Data Export',
            'event': 'Event',
            'no_events': 'No events were found.',
            'reason': 'Reason',
            'pending': 'Pending',
            'fail': 'Fail',
            'pass': 'Pass',
            'surveys': 'Surveys',
            'call_time': 'Call Time',
            'question': 'Question | Questions',
            'download': 'Download',
            'period': 'Period',
            'spanish': 'Spanish',
            'english': 'English',
            'email': 'Email',
            'text': 'Text',
            'password': 'Password',
            'user': 'User',
            'settings': 'Settings',
            'previous': 'Previous',
            'next': 'Next',
            'export': 'Export',
            'add': 'Add',
        },
        'months': {
            'January': 'January',
            'February': 'February',
            'March': 'March',
            'April': 'April',
            'May': 'May',
            'June': 'June',
            'July': 'July',
            'August': 'August',
            'September': 'September',
            'October': 'October',
            'November': 'November',
            'December': 'December',
            '01': 'January',
            '02': 'February',
            '03': 'March',
            '04': 'April',
            '05': 'May',
            '06': 'June',
            '07': 'July',
            '08': 'August',
            '09': 'September',
            '10': 'October',
            '11': 'November',
            '12': 'December',
        },
        'time_units': {
            'day': 'day',
            'week': 'week',
            'month': 'month',
            'year': 'year',
            'hour': 'hour',
            'minute': 'minute',
        },
        'week_days': {
            'sunday': 'Sunday',
            'monday': 'Monday',
            'tuesday': 'Tuesday',
            'wednesday': 'Wednesday',
            'thursday': 'Thursday',
            'friday': 'Friday',
            'saturday': 'Saturday',
        },
    },
    'es': {
        'fuelTypes': {
            'electric': 'electricidad',
            'natural_gas': 'gas natural',
            'dual': 'doble',
        },
        'currency': {
            'cents': 'centavos',
            'dollars': 'dólares',
        },
        'uom': {
            'therm': 'termia',
            'kwh': 'kilovatios-hora',
            'unknown': 'desconocido',
            'ccf': 'centum pies cúbicos',
            'mwhs': 'megavatios-hora',
            'gj': 'gigajoules',
        },
        'identTypes': {
            'Account_Number': 'Número de cuenta',
            'account_name': 'Nombre de la cuenta',
            'Service_Agreement_ID': 'Número de identificación del acuerdo',
            'Customer_Number': 'Número de cliente',
            'Pod_ID': 'Pod ID',
            'Service_Delivery_Identifier': 'Número de identificación del servicio',
            'Service_Number': 'Numero de servicio',
            'Meter_Number': 'Número de medidor',
            'Service_Reference_Number': 'Número de referencia',
            'Name_Key': 'Nombre clave',
            'Billing_Account_Number': 'Número de cuenta de la facturación',
            'Service_Point_ID': 'Identificación del punto de servicio (Service Point ID)',
            'Site_ID': 'Identificación del Sitio',
            'Choice_ID': 'Identificación del Elección',
            'confirmation_code': 'Código de confirmación',
            'vendor_name': 'Nombre del vendedor',
            'first_name': 'Nombre',
            'last_name': 'Apellido',
            'agency': 'Agencia',
            'agent_vendor': 'Agente Vendedor',
            'agent_name': 'Nombre del Agente',
            'sales_agent': 'Agente de Ventas',
            'email_address': 'Dirección de correo electrónico',
            'billing_address': 'Dirección de Envio',
            'service_address': 'Dirección de Servicio',
            'agent': 'Agente',
            'type': 'Tipo',
            'ip_address': 'Dirección IP',
            'vendors': 'Vendedores',
            'username': 'Nombre de Usuario',
            'phone': 'Teléfono',
            'phone_number': 'Número Telefónico',
        },
        'dialogs': {
            'end_call_with_disposition': 'Gracias, marcaremos esta llamada como {disposition}. Si tiene alguna pregunta, llame al servicio al cliente al {cx_service_number}.',
            'end_call_no_disposition': 'Gracias por su tiempo, marcaremos su (s) cuenta (s) como no inscritas en este momento. Si tiene alguna pregunta, llame al servicio al cliente al {cx_service_number} ',
            'btn_is_sales_agent': 'El número de teléfono de facturación (BTN) que figura como línea telefónica de un agente de ventas, proporcione un número alternativo o comuníquese con su supervisor para obtener ayuda',
            'acct_prev_enrolled': 'La cuenta número de identificación {identifier} se ha registrado anteriormente. Ingrese una cuenta válida número de identificación o póngase en contacto con su supervisor para obtener ayuda.',
            'btn_reuse': 'Este número de teléfono de facturación se ha utilizado para verificar varias cuentas recientemente. Verifique la entrada del número, proporcione un número alternativo o comuníquese con su supervisor para obtener ayuda',
            'svc_addr_reuse': 'Esta dirección de servicio se ha utilizado para otra venta. Proporcione una dirección de servicio válida o comuníquese con su supervisor para obtener ayuda',
            'cust_prev_enrolled': 'Nuestros registros indican que el nombre de autorización y el número de teléfono de facturación se han inscrito previamente. Ingrese un nombre válido y BTN o comuníquese con su supervisor para obtener ayuda asistencia.',
            'existing_acct': 'Nuestros registros indican que el nombre de autorización y el número de teléfono de facturación se han inscrito previamente. Ingrese un nombre válido y BTN o comuníquese con su supervisor para obtener ayuda ',
            'voip': 'Parece que el agente de ventas de proveedores está usando un teléfono VOIP',
            'birthday_1': 'Pido disculpas, la fecha de nacimiento que proporcionaste es menor de 18 años.',
            'birthday_2': 'Por favor ingrese el cumpleaños como MMDDYYYY',
            'sales_limit': 'Me disculpo, agente, ha alcanzado el límite de ventas del día. Si tiene alguna pregunta, comuníquese con su supervisor porque no podremos continuar. ¡Gracias, tenga un excelente día!',
            'after_curfew': 'Pido disculpas al agente, no se le permite realizar ventas después del toque de queda. Si tiene alguna pregunta, comuníquese con su supervisor. ¡Gracias, tenga un excelente día!',
            'multi_tpv': 'Nuestros registros indican que el nombre de autorización y el número de teléfono de facturación se han inscrito previamente. Ingrese un nombre válido y BTN o comuníquese con su supervisor para obtener ayuda ',
            'account_number_no_sale': 'Mostramos que esta cuenta ha sido verificada demasiadas veces. Haga que el cliente llame a {brand} Servicio al cliente {cx_service_number} para discutir sus opciones de cuenta.',
            'btn_no_sale_dispositions': 'Lo siento. No podemos inscribir su cuenta en este momento debido a múltiples intentos en la premisa. Si tiene alguna pregunta, comuníquese con {brand} al {cs_service_number}.',
            'account_number_good_sale': 'Mostramos que esta cuenta ha sido verificada demasiadas veces.Haga que el cliente llame a {brand} al {cx_service_number} para discutir sus opciones de cuenta.',
            'btn_no_sale': 'Nuestros registros indican que este número de teléfono de facturación está asociado a una venta previa anterior. Por favor ingrese un BTN válido o contacte a su supervisor para asistencia ',
            'email_reuse': 'Esta dirección de correo electrónico se ha utilizado anteriormente. Proporcione una dirección de correo electrónico alternativa o póngase en contacto con su supervisor para obtener ayuda.',
            'record_id_not_found': 'Lo siento, no se pudo encontrar este ID de registro. Podemos volver a intentar la importación o podemos proceder manualmente.',
            'record_id_existing': 'Lo siento, este ID de registro se ha utilizado para una buena venta anterior. Ingrese un número de confirmación válido o podemos proceder manualmente.',
            'record_id_wrong_state': 'Lo siento, este ID de registro solo es válido en {needstate} pero ha llamado para verificar {instate}.',
            'indra_active_api_fail': 'Indra Energy ha determinado que este cliente no es elegible, no continúe.',
        },
        'ui': {
            'date': 'Fecha',
            'yes': 'Sí',
            'no': 'No',
            'back': 'Regresa',
            'disable': 'Inhabilitar',
            'enable': 'Habilitar',
            'view': 'Ver',
            'edit': 'Editar',
            'delete': 'Borrar',
            'update': 'Actualizar',
            'filter': 'Filtrar',
            'submit': 'Enviar',
            'clear': 'Limpiar',
            'submit': 'Enviar',
            'close': 'Cerrar',
            'welcome': '¡Bienvenido, {name}!',
            'cancel': 'Cancelar',
            'save': 'Guardar',
            'upload': 'Cargar',
            'thankyou': 'Gracias',
            'goodbye': 'Adios',
            'continue': 'Continuar',
            'import': 'Importar',
            'first_name': 'Primer Nombre',
            'm_i': 'Inicial del Segundo Nombre',
            'last_name': 'Apellido',
            'optional': 'opcional',
            'agent': 'Representante',
            'end_call': 'Finalizar llamada',
            'leave_voicemail': 'Dejar el correo de voz',
            'leave_voicemail_exit_reason': 'Hola, llamo en nombre del equipo de control de calidad de ventas de {{client.name}}. Lamentamos no haber podido contactarlo/a el dia de hoy. Intentaremos comunicarnos con usted en un momento más conveniente. ¡Gracias y que tengas un buen día!',
            'survey_feedback': 'Comentarios de la encuesta',
            'change_agent': 'Cambiar el Representante',
            'call_review': 'Revisar Llamada',
            'loading': 'Cargando',
            'required': 'Requerido',
            'search': 'Buscar',
            'sale': 'Venta',
            'good_sale': 'Buena Venta',
            'no_sale': 'No Venta',
            'good_sales': 'Buenas Ventas',
            'no_sales': 'No Ventas',
            'total_sales': 'Total de Ventas',
            'close_sale': 'Cerrar llamada',
            'choose': 'Escoger',
            'finish': 'Terminar',
            'address': 'Dirección',
            'zip_code': 'Código postal',
            'postal_code': 'Código postal',
            'city': 'Ciudad',
            'state': 'Estado',
            'a_and_b': '{a} y {b}',
            'province': 'Provincia',
            'vendor': 'Vendedor',
            'vendors': 'Vendedores',
            'brand': 'Compañía',
            'office': 'Oficina',
            'commodity': 'Mercancía',
            'market': 'Mercado',
            'channel': 'Canal',
            'channels': 'Canales',
            'language': 'Languaje',
            'result': 'Resultado',
            'product': 'Producto',
            'bill_name': 'Nombre en la factura',
            'disposition': 'Disposición',
            'confirmation': 'Confirmación',
            'home': 'Inicio',
            'reports': 'Reportes',
            'data_export': 'Exportar datos',
            'event': 'Evento',
            'no_events': 'No se encontraron eventos.',
            'reason': 'Razón',
            'pending': 'Pendiente',
            'fail': 'Fallar',
            'pass': 'Aprobar',
            'surveys': 'Encuestas',
            'call_time': 'Tiempo de llamada',
            'question': 'Pregunta | Preguntas',
            'download': 'Descargar',
            'period': 'Período',
            'spanish': 'Español',
            'english': 'Inglés',
            'email': 'Email',
            'text': 'Texto',
            'password': 'Contraseña',
            'user': 'Usuario',
            'settings': 'Ajustes',
            'previous': 'Anterior',
            'next': 'Próximo',
            'export': 'Exportar',
            'add': 'Agregar',
        },

        'months': {
            'January': 'Enero',
            'February': 'Febrero',
            'March': 'Marzo',
            'April': 'Abril',
            'May': 'Mayo',
            'June': 'Junio',
            'July': 'Julio',
            'August': 'Agosto',
            'September': 'Septiembre',
            'October': 'Octubre',
            'November': 'Noviembre',
            'December': 'Diciembre',
            '01': 'Enero',
            '02': 'Febrero',
            '03': 'Marzo',
            '04': 'Abril',
            '05': 'Mayo',
            '06': 'Junio',
            '07': 'Julio',
            '08': 'Agosto',
            '09': 'Septiembre',
            '10': 'Octubre',
            '11': 'Noviembre',
            '12': 'Diciembre',
        },
        'time_units': {
            'day': 'día',
            'week': 'semana',
            'month': 'mes',
            'year': 'año',
            'hour': 'hora',
            'minute': 'minuto',
        },
        'week_days': {
            'sunday': 'Domingo',
            'monday': 'Lunes',
            'tuesday': 'Martes',
            'wednesday': 'Miércoles',
            'thursday': 'Jueves',
            'friday': 'Viernes',
            'saturday': 'Sábado',
        },
    },
};
