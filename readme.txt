Hi Teamlead,

The meaning of the solution:
1. All POST requests create an event in the data bus only.
2. Updating the data in the database is done with the help of the consumer.
3. Recording to the database occurs after processing several messages from the queue.
   Thus, a record in the database will occur only once every few seconds.
4. GET request takes data from the memcache.
5. A separate task writes the data to the memcache every few seconds.
6. The table with statistics has a partiton, the partition can be done by years, months or another period.
7. To determine the 5 most popular countries, there is a separate table with global statistics.
8. This table is updated by the consumer, but it can also sometimes be updated by the query to the main log table.
9. Also I can archive old records to reduce the log table.
   In doing so, I will take into account the archived data in the table to determine the top of countries.

HTTP API:
1. POST `web/post.php`
   BODY like: `{"country": "RU", "event": "play"}`
2. GET `web/get.php?format=csv`
3. GET `web/get.php?format=json` (json by default)

How to run:
1. `php consumer/calc.php` – main consumer
2. `php commands/requester.php` – just to create test data.
3. `php/cacher.php` – It should be run every few seconds to update the GET metod's cache.


Due to the fact that the main requirement is performance, I left the code in a very simple version.
Of course, I can make settings in separate files, that would not duplicate them, but I think the meaning of the task is not in this.

Mysql DDL:

create table statistic_global
(
  country varchar(3) not null primary key,
  count bigint default '0' null,
  constraint statistic_countrty_uindex unique (country)
);


create table log
(
  date date not null,
  country varchar(3) not null,
  event varchar(10) not null,
  count bigint default '0' null,
  constraint log_date_country_event_pk unique (date, country, event)
);

ALTER TABLE log PARTITION BY RANGE (YEAR(date))
(
  PARTITION log2014 VALUES LESS THAN (2015) ,
  PARTITION log2015 VALUES LESS THAN (2016) ,
  PARTITION log2016 VALUES LESS THAN (2017) ,
  PARTITION log2017 VALUES LESS THAN (2018)
);



Original task:

T3JpZ2luYWwgdGFzazoNCg0KSW1hZ2luZSB0aGF0IHlvdSBoYXZlIGFuIGFwcGxpY2F0aW9uIHdpdGggbWlsbGlvbnMgb2YgdXNlcnMuIFBlcmZvcm1hbmNlIGlzIGtleS4NCllvdSBuZWVkIHRvIGNyZWF0ZSBhIGJhY2tlbmQgZm9yIGl0IHdoaWNoIHdpbGwgaGFuZGxlIHRoZSBmb2xsb3dpbmcgdHdvIHJlcXVlc3RzLg0KVGhlIGJhY2tlbmQgaGFzIGEgZGF0YWJhc2Ugd2hpY2gga2VlcHMgY291bnRlcnMgZm9yIGVhY2ggZGF5LCBjb3VudHJ5IGFuZCBldmVudC4NCkV2ZW50IGNhbiBiZSBhbnkgb2YgInZpZXciLCAicGxheSIgb3IgImNsaWNrIg0KRS5nLg0KDQoyMDE3LTA3LTAxIFVTIHZpZXdzIDUwMDAwDQoyMDE3LTA3LTAxIFVTIHBsYXlzIDEwMA0KMjAxNy0wNy0wMiBVUyB2aWV3cyAzMDAwDQoyMDE3LTA3LTAxIENBIGNsaWNrcyAxMjMNCi4uLg0KDQoxLiBSZWNlaXZlIGRhdGEgZnJvbSBhcHBsaWNhdGlvbi4gVGhlIGRhdGEgaXMgc2VudCBieSBQT1NULiBUaGUgZGF0YSBpcyBmb3JtYXR0ZWQgaW4ganNvbi4NClRoZSBiYWNrZW5kIG5lZWRzIHRvIGRlY29kZSB0aGlzIGRhdGEgYW5kIGV4dHJhY3QgdGhlICJjb3VudHJ5IiBhbmQgImV2ZW50IiBmaWVsZHMuDQpUaGVuIHRoZSBiYWNrZW5kIG5lZWRzIHRvIGluY3JlbWVudCBhIGNvdW50ZXIgaW4gdGhlIGRhdGFiYXNlIGZvciB0aGUgY3VycmVudCBkYXkNCmZvciB0aGUgcmVzcGVjdGl2ZSBjb3VudHJ5IGFuZCBldmVudC4NCg0KMi4gVGhlIGFwcGxpY2F0aW9uIGRvZXMgYSBHRVQgcmVxdWVzdC4gRGF0YSBzaG91bGQgYmUgcmV0dXJuZWQgaW4gZGlmZmVyZW50IGZvcm1hdHMgKGpzb24sY3N2KQ0KYWNjb3JkaW5nIHRvIHRoZSByZXF1ZXN0IHBhcmFtZXRlcnMuIFRoZSByZXNwb25zZSBzaG91bGQgY29udGFpbiB0aGUgc3VtIG9mIGVhY2ggZXZlbnQNCm92ZXIgdGhlIGxhc3QgNyBkYXlzIGJ5IGNvdW50cnkgZm9yIHRoZSB0b3AgNSBjb3VudHJpZXMgb2YgYWxsIHRpbWVzLg0KDQpOb3RlczoNCg0KVXNlIG9ubHkgcHVyZSBQSFAuIERvIG5vdCB1c2UgYW55IGZyYW1ld29yaw0KVGhlIHRhYmxlIHdpbGwgZXZlbnR1YWxseSBob2xkIG1pbGxpb25zIG9mIHJvd3MgYW5kIHRoZSBhcGkgd2lsbCBnZXQgZG96ZW5zIG9mIHJlcXVlc3RzIHBlciBzZWNvbmQuIFJldHVybmluZyAxMDAlIHVwMmRhdGUgaW5mb3JtYXRpb24gaW4gcmVzcG9uc2VzIGlzIG5vdCBhIHJlcXVpcmVtZW50IGJ1dCBmYXN0IHJlc3BvbnNlcyBhcmUu
