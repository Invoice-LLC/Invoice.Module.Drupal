<h1>Invoice Drupal 8-9(commerce) plugin</h1>

<h3>Требования</h3>
1. Установленный Drupal 8 или 9 версии<br>
2. Установленный плагин Commerce 2

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.Drupal/archive/master.zip) и скопируйте содержимое архива в папку корень сайта
2. Перейдите во вкладку **Расширения** и установите модуль "Invoice"
![Imgur](https://imgur.com/4uBCKuZ.png)
3. Перейдите во вкладку **Commerce->Конфигурация->Payment gateways** и нажмите "Add payment gateway"
![Imgur](https://imgur.com/W0u6ImA.png)
4. Впишите свой API Key и логин из личного кабинета Invoice
![Imgur](https://imgur.com/v2giEyB.png)
5. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
с типом **WebHook** и адресом: **%URL сайта%/payment/notify/invoice**
![Imgur](https://imgur.com/LZEozhf.png)
