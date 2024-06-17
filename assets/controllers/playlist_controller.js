import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    deletePlaylist(event) {
        event.preventDefault();

        const url = this.element.dataset.url;

        console.log(url);

        fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
            .then(response => {
                if (response.ok) {
                    console.log('Плейлист удален');
                    window.location.href = '/';
                } else {
                    console.error('Ошибка удаления плейлиста');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
            });
    }

}
