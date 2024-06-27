import {Controller} from '@hotwired/stimulus';
import { Fancybox } from '@fancyapps/ui';
import "../styles/fancybox.css"

export default class extends Controller {
    downloadVideo(event) {
        event.preventDefault();
        const videoId = this.element.dataset.videoId;
        const playlistId = this.element.dataset.playlistId;

        window.location.href = `/api/playlist/${playlistId}/download/${videoId}`;
    }

    connect() {
        Fancybox.bind('[data-fancybox]', {

        });
    }


    static targets = ['link'];

    openVideo(event) {
        event.preventDefault();
        const videoId = event.currentTarget.dataset.videoId;

        const videoUrl = `/api/video/${videoId}/watch`;

        fetch(videoUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.blob();
            })
            .then(blob => {
                const objectUrl = URL.createObjectURL(blob);

                Fancybox.show([{
                    src: `<video controls style="width: 100%; height: 85vh;">
                  <source src="${objectUrl}" type="video/mp4">
                  Your browser does not support the video tag.
                </video>`,
                    type: 'html',
                }]);
            })
            .catch(error => {
                console.error('Error fetching video:', error);
            });
    }
}
