Сформируй управленческий дайджест по чату Bitrix24.

Параметры:
- Текущая дата и время: {{current_datetime}}
- Таймзона пользователя: {{timezone}}
- Период анализа: {{summary_depth}}
- Роль пользователя: {{requester_role}}
- Имя пользователя: {{requester_name}}
- Название чата: {{chat_title}}
- Тип чата: {{chat_type}}
- Участники чата: {{participants}}
- Язык ответа: русский

Цель отчета:
Руководитель открыл чат после пропуска периода и хочет понять:
1. Что важного произошло.
2. Какие решения были приняты.
3. Где есть риски, блокеры или открытые вопросы.
4. Какие задачи, сделки и файлы Bitrix24 затронуты.
5. Какие действия нужны дальше и кому.

Данные по найденным сущностям Bitrix24:
<bitrix_entities>
{{bitrix_entities_json}}
</bitrix_entities>

История сообщений чата:
Каждое сообщение содержит:
- message_id
- datetime
- author_id
- author_name
- text
- links
- attachments
- reactions, если есть
- reply_to_message_id, если есть

<chat_messages>
{{chat_messages_json}}
</chat_messages>

Верни результат строго в JSON по указанной структуре.
Не добавляй markdown, комментарии или пояснения вне JSON.