// js/calendar_admin.js

document.addEventListener('DOMContentLoaded', function(){
    const calendarEl = document.getElementById('calendar');
    let selected_start_string = null;
    let event_selected = false; // pour event.remove()

    const calendar = new FullCalendar.Calendar(calendarEl,{
        editable: true,
        locale: 'fr',
        //timeZone: 'local', // à modifier pour être à l'heure d'un autre pays
        initialView: 'dayGridMonth',
        headerToolbar:{
            left: 'prev,next today',
            center: 'title',
            //right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        slotMinWidth: 70,
        defaultAllDay: false,

        // numéros de semaine
        //weekNumbers: true,
        //weekText: 's',
        
        // vue mois
        contentHeight: 600, // après initialisation: calendar.setOption('contentHeight', 650);
        //aspectRatio: 1.5, // après initialisation: calendar.setOption('aspectRatio', 1.8);
        // pour recalculer la taille au redimensionnement du parent, exécuter: calendar.updateSize()
        stickyHeaderDates: true, // garder les en-tête de colonnes lors du scroll
        fixedWeekCount: false, // avec false, affiche 4, 5 ou 6 semaines selon le mois
        selectable: true, // sélection de jours multiples
        longPressDelay: 0, /* par défaut sur mobile, select est déclenché avec un appui d'une seconde,
        chatgpt déconseille 0 par risque de conflit entre selection et scrolling, mettre plutôt 200 ou 300ms */
        navLinks: true, // numéros de jour et de semaines clicables
        
        // vue semaine
        slotEventOverlap: true, // superposition (limitée) de deux évènements simultanés
        allDayContent: 'Journée', // texte dans la case "toute la journée"
        nowIndicator: true, // barre rouge pour maintenant

        // params en plus: https://fullcalendar.io/docs/events-json-feed
        events: 'index.php?action=get_events', // fichier PHP qui retourne les événements
        
        select: function(info){
            selected_start_string = info.startStr; // variable "globale"
            event_selected = false;
            const aside = document.querySelector('aside');
            let checked = '';
            let input = 'datetime-local';

            // on veut des chaines de la forme 2025-05-20T07:05
            // il faut retirer les secondes et le fuseau horaire du format ISO, c'est chiant
            // on enverra par contre une chaine ISO au serveur pour avoir un enregistrement correct
            
            let start_value;
            let end_value;
            const end = new Date(info.endStr);

            if(calendar.view.type == 'dayGridMonth'){
                start_value = info.startStr + 'T10:00';
                end.setDate(end.getDate() - 1); // jour de fin modifié pour ne pas faire bizarre pour l'utilisateur
                end.setHours(11);
                end_value = end.toISOString().split('T')[0] + 'T11:00';
            }
            else if(calendar.view.type == 'timeGridWeek' || calendar.view.type == 'timeGridDay'){
                const start_array = info.startStr.split("T");
                const end_array = info.endStr.split("T");

                // clic sur la ligne "Journée", = 'dayGridMonth'
                if(start_array.length == 1){
                    checked = 'checked';
                    input = 'date';
                    start_value = info.startStr;
                    end.setDate(end.getDate() - 1);
                    end_value = end.toISOString().split('T')[0];
                }
                else if(start_array.length == 2){
                    start_value = start_array[0] + "T" + start_array[1].substr(0,5); // format 2025-06-12T10:00
                    end_value = end_array[0] + "T" + end_array[1].substr(0,5);
                }
            }

            const aside_content = `<div class="form_event">
                    <div class="event_title_box">
                        <h2>Nouvel évènement</h2>
                    </div>
                    <div class="">
                        <label for="event_title">Nom</label>
                        <input type="text" id="event_title">
                    </div>
                    <div class="">
                        <label for="event_description">Description</label>
                        <textarea id="event_description" cols="27"></textarea>
                    </div>
                    <div class="">
                        <input type="checkbox" id="event_all_day" class="event_all_day" ` + checked + `>
                        <label for="event_all_day">Journée entière</label>
                    </div>
                    <div class="">
                        <label for="event_start">Début</label>
                        <input type="` + input + `" id="event_start" value="` + start_value + `">
                    </div>
                    <div class="">
                        <label for="event_end">Fin</label>
                        <input type="` + input + `" id="event_end" value="` + end_value + `">
                    </div>
                    <div class="">
                        <label for="event_color">Couleur</label>
                        <input type="color" id="event_color" value="#3788D8">
                    </div>
                    <button class="submit_new_event">Enregistrer</button>
                    <button class="event_close_button">Annuler</button>
                </div>`;
            aside.innerHTML = aside_content;
            calendar.updateSize();
        },
        //unselect: function(event, view){},
        eventClick: function(info){
            event_selected = true; // variable "globale"
            const aside = document.querySelector('aside');
            const checked = info.event.allDay ? 'checked' : '';
            const input = info.event.allDay ? 'date' : 'datetime-local';

            // change des objets Date en chaînes compatibles avec les input
            function formatDate(date){
                return date.getFullYear() + '-' + (date.getMonth() + 1).toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0')
                    + (info.event.allDay ? '' : 'T' + date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0'));
            }
            function minusOneDay(date){
                date.setDate(date.getDate() - 1); // jour de fin modifié pour ne pas faire bizarre pour l'utilisateur
                return date;
            }

            const formated_start = formatDate(info.event.start);
            const formated_end = formatDate(info.event.allDay ? minusOneDay(info.event.end) : info.event.end, info.event.allDay);
            
            const aside_content = `<div class="form_event">
                    <div class="event_title_box">
                        <h2>Modifier un évènement</h2>
                    </div>
                    <div class="">
                        <label for="event_title">Nom</label>
                        <input type="text" id="event_title" value="` + info.event.title + `">
                        <input type="hidden" id="event_id" value="` + info.event.id + `">
                    </div>
                    <div class="">
                        <label for="event_description">Description</label>
                        <textarea id="event_description" cols="27">` + info.event.extendedProps.description + `</textarea>
                    </div>
                    <div class="">
                        <input type="checkbox" id="event_all_day" class="event_all_day" ` + checked + `>
                        <label for="event_all_day">Journée entière</label>
                    </div>
                    <div class="">
                        <label for="event_start">Début</label>
                        <input type="` + input + `" id="event_start" value="` + formated_start + `">
                    </div>
                    <div class="">
                        <label for="event_end">Fin</label>
                        <input type="` + input + `" id="event_end" value="` + formated_end + `">
                    </div>
                    <div class="">
                        <label for="event_color">Couleur</label>
                        <input type="color" id="event_color" value="` + info.event.backgroundColor + `">
                    </div>
                    <button class="submit_update_event">Modifier</button>
                    <button class="event_close_button">Annuler</button>
                    <button class="delete_event">Supprimer</button>
                </div>`;
            aside.innerHTML = aside_content;
            calendar.updateSize();
        },
        viewDidMount: function(info){ // déclenché lorsque qu'une nouvelle vue est chargée (mois, semaine...)
            if(selected_start_string){
                calendar.gotoDate(new Date(selected_start_string));
            }
        },
        //datesSet: function(info){}, // déclenché lorsque des dates affichées sont chargées (= comme viewDidMount + changement de date)
    });
    
    function hideModal(){
        const aside = document.querySelector('aside');
        event_selected = false;
        aside.innerHTML = '';
        calendar.updateSize();
    }

    function submitEvent(new_event){
        const event_title = document.getElementById('event_title').value;
        const event_description = document.getElementById('event_description').value;
        const event_all_day = document.getElementById('event_all_day').checked;
        let event_start = document.getElementById('event_start').value;
        let event_end = document.getElementById('event_end').value;
        const event_color = document.getElementById('event_color').value; // #3788d8 par défaut
        const event_id = new_event ? '' : document.getElementById('event_id').value;

        if(event_title.length !== 0 && event_start.length !== 0 && event_end.length !== 0 && event_color.length !== 0
            && (new_event || event_id.length !== 0))
        {
            if(event_all_day){
                // on remet le jour de fin exclu
                const tmp_object = new Date(event_end);
                tmp_object.setDate(tmp_object.getDate() + 1);
                event_end = tmp_object.toISOString().split('T')[0];
            }
            else{
                event_start = new Date(event_start).toISOString();
                event_end = new Date(event_end).toISOString();
            }

            if(event_start > event_end || (!event_all_day && event_start == event_end)){
                return;
            }

            // création
            if(new_event){
                const event = {
                    title: event_title,
                    description: event_description,
                    allDay: event_all_day,
                    start: event_start,
                    end: event_end,
                    color: event_color
                };

                fetch('index.php?action=new_event', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(event),
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success){
                        event.id = data.id;
                        calendar.addEvent(event);
                        hideModal();
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
                
            }
            // modification
            else{
                const event = calendar.getEventById(event_id);

                if(event){
                    const event_copy = {
                        id: parseInt(event.id),
                        description: event_description,
                        title: event_title,
                        allDay: event_all_day,
                        start: event_start,
                        end: event_end,
                        color: event_color
                    };

                    fetch('index.php?action=update_event', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(event_copy),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success){
                            event.setProp('title', event_title);
                            event.setExtendedProp('description', event_description);
                            event.setAllDay(event_all_day);
                            event.setStart(event_start);
                            event.setEnd(event_end);
                            event.setProp('color', event_color);
                            hideModal();
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                    });
                }
                else{
                    console.log("Événement non trouvé !");
                }
            }
        }
        else{
            // notif input vide
            console.log('erreur: input vide');
        }
    }

    function checkAllDay(){
        const event_start_input = document.getElementById('event_start');
        const event_end_input = document.getElementById('event_end');

        const start = event_start_input.value;
        const end = event_end_input.value;

        if(document.getElementById('event_all_day').checked){
            event_start_input.type = 'date';
            event_end_input.type = 'date';

            event_start_input.value = start.split('T')[0];
            event_end_input.value = end.split('T')[0];
        }
        else{
            event_start_input.type = 'datetime-local';
            event_end_input.type = 'datetime-local';

            event_start_input.value = start + 'T10:00';
            event_end_input.value = end + 'T11:00';
        }
    }
    function removeEvent(){
        if(!confirm("Voulez-vous vraiment supprimer cet évènement du calendrier?")){
            return;
        }
        const event_id = document.getElementById('event_id').value;
        const event = calendar.getEventById(event_id);
        
        fetch('index.php?action=remove_event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({'id': event_id}),
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                event.remove();
                hideModal();
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        });
        event_selected = false;
    }

    // touches de clavier
    document.addEventListener('keydown', function(event){
        if(event.key === 'Escape'){
            hideModal();
        }
        else if(event.key === 'Delete' && event_selected === true){
            removeEvent();
        }
    });

    // boutons dans la "modale"
    // technique de la délégation d'événements pour utiliser un bouton ajouté dynamiquement
    document.addEventListener('click', function(event){
        if(event.target.classList.contains('event_close_button')){
            hideModal();
        }
        else if(event.target.classList.contains('event_all_day')){
            checkAllDay();
        }
        else if(event.target.classList.contains('submit_new_event')){
            submitEvent(true);
        }
        else if(event.target.classList.contains('submit_update_event')){
            submitEvent(false);
        }
        else if(event.target.classList.contains('delete_event')){
            removeEvent();
        }
    });

    calendar.render();
});