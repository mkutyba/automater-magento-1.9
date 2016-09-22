# Moduł Magento 1.9.x do integracji z Automater.pl
### Instrukcja instalacji
1. Pobierz paczkę instalacyjną - [automater_magento1.9_latest.zip](http://automater.pl/files/plugins/automater_magento1.9_latest.zip)
2. Rozpakuj wtyczkę i skopiuj ją do katalogu sklepu na swoim serwerze
3. Zaloguj się do panelu administracyjnego sklepu i przejdź do zakładki System / Cache Management
4. Wyczyść cache sklepu klikając w przyciski po prawej stronie: Flush Magento Cache i Flush Cache Storage
5. Przejdź do zakładki System / Configuration
6. W sekcji Sales wybierz pozycje Automater.pl
7. Zaloguj się do systemu Automater.pl i przejdź do zakładki Ustawienia / ustawienia / API
8. Jeśli klucze nie są wygenerowane kliknij w przycisk Wygeneruj nowe klucze
9. Przepisz wartości API Key i API Secret do sklepu internetowego i uruchom wtyczkę ustawiając pozycje Enabled na Yes
10. W ustawieniach produktów w sklepie internetowym wyświetli się nowa pozycja - ID produktu w Automater, wystarczy wybrać istniejący produkt z listy (jeśli nie ma odpowiednika to należy go utworzyć w panelu Automater w zakładce Produkty / Sklep / lista produktów
11. Gotowe - od teraz produkty powiązane z Automater będą wysyłane automatycznie.
