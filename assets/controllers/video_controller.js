import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    downloadVideo(event) {
        event.preventDefault();
        const videoId = this.element.dataset.videoId;
        const playlistId = this.element.dataset.playlistId;

        window.location.href = `/api/playlist/${playlistId}/download/${videoId}`;
    }
}
