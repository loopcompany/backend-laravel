import './bootstrap';

import { createApp } from 'vue';
import CardEditor from './components/CardEditor.vue';

const mountElement = document.getElementById('card-editor-app');

if (mountElement) {
	createApp(CardEditor, {
		editorUrl: mountElement.dataset.editorUrl,
		saveUrl: mountElement.dataset.saveUrl,
		uploadUrl: mountElement.dataset.uploadUrl,
		createCardUrl: mountElement.dataset.createCardUrl,
	}).mount(mountElement);
}
