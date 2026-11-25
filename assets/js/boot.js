'use strict';
// Titik masuk: pulihkan sesi login, lalu buka layar Belajar milik kursus aktif.
(async () => { try { me = (await api('me')).user; homeView(); } catch (e) { authView(); } })();
