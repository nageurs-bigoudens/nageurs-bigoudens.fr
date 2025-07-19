// js/calendar.js

document.addEventListener('DOMContentLoaded', function(){
    const calendarEl = document.getElementById('calendar');
    let selected_start_string = null;

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
            hideModal();
        },
        // méthode alternative à longPressDelay: 0 pour obtenir une sélection d'un simple "tap" sur écran tactile (mettre le if inverse dans select)
        /*dateClick: function(info) {
            if (window.matchMedia('(pointer: coarse)').matches) {
                // utile sur mobile/tablette : déclenche sur un tap
                console.log('dateClick', info.dateStr);
                calendar.select(info.date, info.date); // hack permettant de sélectionner une journée seule uniquement
            }
        },*/
        //unselect: function(event, view){},

        eventClick: function(info){
            const aside = document.querySelector('aside');
            const checked = info.event.allDay ? 'checked' : '';

            // change des objets Date en chaînes compatibles avec les input
            function formatDate(date){
                return date.getFullYear() + '-' + (date.getMonth() + 1).toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0')
                    + (info.event.allDay ? '' : 'T' + date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0'));
            }
            function minusOneDay(date){
                date.setDate(date.getDate() - 1); // jour de fin modifié pour ne pas faire bizarre pour l'utilisateur
                return date;
            }

            const start = formatDate(info.event.start);
            const start_date = start.split('T')[0];
            const start_hour = (info.event.allDay ? '' : '<br>à ' + start.split('T')[1]).replace(":", "h");
            const formated_start = start_date.split('-')[2] + '/' + start_date.split('-')[1] + '/' + start_date.split('-')[0] + start_hour;
            const end = formatDate(info.event.allDay ? minusOneDay(info.event.end) : info.event.end, info.event.allDay);
            const end_date = end.split('T')[0];
            const end_hour = (info.event.allDay ? '' : '<br>à ' + end.split('T')[1]).replace(":", "h");
            const formated_end = end_date.split('-')[2] + '/' + end_date.split('-')[1] + '/' + end_date.split('-')[0] + end_hour;

            let aside_content = `<div class="event" style="border-color: ` + info.event.backgroundColor +`;">
                    <h3>` + info.event.title + `</h3>
                    <p><i>` + info.event.extendedProps.description + `</i></p>`;
            if(checked && (formated_start === formated_end)){ // affichage simplifié évènement d'un jour
                aside_content = aside_content + `<p>le ` + formated_start + `</p>`;
            }
            else{
                aside_content = aside_content + `<p>du ` + formated_start + `</p>
                        <p>au ` + formated_end + `</p>`;
            }
            aside_content += `<button class="event_close_button">Fermer</button>
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
        aside.innerHTML = '';
        calendar.updateSize();
    }

    document.addEventListener('keydown', function(event){
        if(event.key === 'Escape'){
            hideModal();
        }
    });

    // technique de la délégation d'événements pour utiliser un bouton ajouté dynamiquement
    document.addEventListener('click', function(event){
        if(event.target.classList.contains('event_close_button')){
            hideModal();
        }
    });

    calendar.render();
});