# ecowitt_http_gateway
Simple HTTP gateway that receives data from GW-1000 with Ecowitt protocol and resend data to Meteotemplate or csv, json, ecc.

Il GW-1000 permette di inviare i dati oltre che a Ecowitt.net e Wunderground, anche ad un sito esterno purché si selezioni uno 
dei due precedenti protocolli.<br>
Il protocollo Wunderground lo conosciamo e sappiamo che non permette l'invio dei dati UV e PM2.5, nè temperatura suolo o 
altri sensori aggiuntivi, così bisogna selezionare il protocollo Ecowitt.

A questo punto bisogna avere un server web su cui mandare i dati.

Il server web deve avere queste caratteristiche:
- possibilità di creare una directory /data/report (es. /var/www/html/data/report )
- nella directory report dovrà esservi il file index.php 

Quindi il sito da contattare sarà ad esempio: http://192.168.1.4/data/report/index.php<br>
Nella configurazione del GW-1000 basterà semplicemente scrivere l'indirizzo IP, es. 192.168.1.4 e specificare la frequenza di invio dati.

Io consiglio di avere questo server web su una raspberry, nella stessa rete in cui risiede il GW-1000, in questo modo lo script lo si potrà 
utilizzare anche per conservare i dati.<br> 
Quando il GW-1000 contatterà la vostra raspberry, il file index.php farà queste tre funzioni:

1) crea un file .JSON in /var/log/ecowitt ( che riscrive ad ogni upload, contiene solo gli ultimi dati )<br>
2) crea un file .CSV in /var/log/ecowitt ( va in append, quindi conserva tutti i dati )<br>
3) converte in metrico i dati e li reinvia ad un sito meteotemplate<br>

SIMPLE INSTALL GUIDE:
- Install Apache
- Install PHP
- Install jq ( for JSON query )
- Create directory Es. /var/www/html/data/report/
- Create /var/log/ecowitt with chmod 777
- Put file: index.php 
- Configure index.php
- Configure GW-1000 to send data to you server

Look in /var/log/ecowitt to read fields using 'jq'

jq -r '.tempc' weather_XXXXXXXXXXXXXXXX.json

NEXT STEPS of IMPROVEMENTS:
- migliorare la scrittura del file .csv
- inviare il file .JSON su un sito ftp in modo da farlo gestire a Meteonetwork, ad esempio, per il prelievo dei dati
- creare un connector verso weewx
