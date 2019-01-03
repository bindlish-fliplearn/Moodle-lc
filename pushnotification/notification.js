 if ('serviceWorker' in navigator) {
            console.log('Service Worker and Push is supported');
            navigator.serviceWorker.register('/pushnotification/serviceworker.js')
                .then(function (registration) {
                    console.log('Service Worker is registered', registration);
                    
                })
                .catch(function (error) {
                    console.error('Service Worker Error', error);
                });
        } else {
            console.warn('Push messaging is not supported');
        }    
        // Initialize Firebase
        var config = {
            apiKey: "AIzaSyCbxoGEKR6q-lDg7p6VwRufgbx4z8Z-AAQ",
            authDomain: "pwa-live.firebaseapp.com",
            databaseURL: "https://pwa-live.firebaseio.com",
            projectId: "pwa-live",
            storageBucket: "pwa-live.appspot.com",
            messagingSenderId: "855378505035"
        };

        firebase.initializeApp(config);
        const messaging = firebase.messaging();
        messaging.requestPermission().then(function () {
            return messaging.getToken();
        }).then(function (token) {
            console.log('Firebase token: ', token);
            var  BL_URL =  '';
            var  headers =  {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                };
            var domainName = document.location.hostname;
            var deviceInfo = {
                "device": {
                    "actionedBy": "1234",
                    "appVersion": 1,
                    "platform": window.navigator.platform,
                    "osVersion": window.navigator.appVersion,
                    "osName": "web",
                    "serialNo": token,
                    "arnToken": token,
                    "Imei": token,
                    "MacAddress": "",
                    "DeviceModel": "",
                    "domainName":domainName,
                }
            }         
            $.ajax({
                    type: "get",
                    url: "/pushnotification/notificationConfig.php",
                    success: function(result){
                                var jsonObj =  JSON.parse(result);
                                if(jsonObj.error == ''){
                                    UMS_URL =  jsonObj.UMS_URL ;
                                    deviceInfo.device.actionedBy = jsonObj.uuid;
                                    $.ajax({
                                        type: "POST",
                                        headers: headers,
                                        url: UMS_URL+"/registerDevice",
                                        data: deviceInfo,
                                        success: function(result){
                                            console.log('data')
                                            console.log(result);                            }
                                    });
                            }
                    }
                });

            localStorage.setItem("WEB_FIREBASE_ARN_TOKEN", token);
        }).catch(function (err) {
            console.log('Permission denied', err);
        });
        messaging.onMessage(function (payload) {
            console.log('onMessage: ', payload);
        var n = new Notification(payload.notification.title,payload);
        });