# dropfiles

---

### Создать админа

> php bin/console admin:create-new

Будет создан токен, указав его можно зайти в админ панель.

Пример
http://localhost:8080/admin?token=9730c71e-7f7e-4b03-8bda-47f83fe78fce

---

Обновления токена админа

> php bin/console admin:refresh-token


---

## workflow Статусы
- В очереди
- В обработке
- Завершено

[Список мини тасок](TODO.md)