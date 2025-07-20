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
        selectable: true, // sélection de jours en cliquant dessus
        longPressDelay: 300, // 1000ms par défaut
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
        // sélection d'une date simple sur mobile, évite des problèmes de conflit avec eventClick
        dateClick: function(info){
            if(window.matchMedia('(pointer: coarse)').matches){
                const end = new Date(info.date.getTime());
                calendar.view.type == 'dayGridMonth' ? end.setDate(end.getDate() + 1) : end.setMinutes(end.getMinutes() + 30);
                // vue date: la fin est une date exclue
                // vue semaine: durée de 30min par défaut

                calendar.select(info.date, end); // appeler select() avec un seul paramètre ne marche pas avec la vue "mois"
            }
        },
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