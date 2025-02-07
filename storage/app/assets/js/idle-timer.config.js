        (function ($) {
            var var_ruta = "/Collide";
            var
                session = {
                    inactiveTimeout: 5000,     //(ms) Tiempo de inactividad permitido
                    warningTimeout: 5000,      //(ms) Tiempo del mensaje de inactividad
                    minWarning: 5000,           //(ms) If they come back to page (on mobile), The minumum amount, before we just log them out
                    warningStart: null,         //Date time the warning was started
                    warningTimer: null,         //Timer running every second to countdown to logout
                    logoutUrl: var_ruta + "/ajax/sv3000_destroySession",
                    counterLogout: 0,
                    logout: function () {       //Logout function once warningTimeout has expired
                        if(session.counterLogout == 0){
                            // Solo ejecutamos si no se ejecuto nunca.
                            $.ajax({ url: session.logoutUrl });
                        }
                        session.counterLogout = session.counterLogout + 1;
                        $("#mdlLoggedOut").modal({backdrop: 'static', keyboard: false},"show");
                    },

                    //Keepalive Settings
                    keepaliveTimer: null,
                    keepaliveUrl: var_ruta + "/ajax/sv3000_updateSession",
                    keepaliveInterval: 6000,     //(ms) Tiempo para hacer el update.
                    keepAlive: function () {
                        if(session.counterLogout == 0){
                            $.ajax({ url: session.keepaliveUrl });
                        }
                    }
                }
            ;
            $(document).on("idle.idleTimer", function (event, elem, obj) {
                //Get time when user was last active
                var
                    diff = (+new Date()) - obj.lastActive - obj.timeout,
                    warning = (+new Date()) - diff
                ;
                
                //On mobile js is paused, so see if this was triggered while we were sleeping
                if (diff >= session.warningTimeout || warning <= session.minWarning) {
                    $("#mdlLoggedOut").modal({backdrop: 'static', keyboard: false},"show");
                } else {
                    //Show dialog, and note the time
                    $('#sessionSecondsRemaining').html(Math.round((session.warningTimeout - diff) / 1000));
                    $("#myModal").modal({backdrop: 'static', keyboard: false},"show");
                    session.warningStart = (+new Date()) - diff;

                    //Update counter downer every second
                    session.warningTimer = setInterval(function () {
                        var remaining = Math.round((session.warningTimeout / 1000) - (((+new Date()) - session.warningStart) / 1000));
                        if (remaining >= 0) {
                            $('#sessionSecondsRemaining').html(remaining);
                        } else {
                            session.logout();
                        }
                    }, 1000)
                }
            });
            // create a timer to keep server session alive, independent of idle timer
            session.keepaliveTimer = setInterval(function () {
                session.keepAlive();
            }, session.keepaliveInterval);
            //User clicked ok to extend session
            $("#extendSession").click(function () {
                clearTimeout(session.warningTimer);
            });
            //User clicked logout
            $("#logoutSession").click(function () {
                session.logout();
            });
            $(document).idleTimer(session.inactiveTimeout);
        })(jQuery);