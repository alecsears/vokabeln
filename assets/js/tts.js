// Text-to-Speech helper
const TTS = {
    speak(text, lang = 'en-US') {
        if (!window.speechSynthesis) return;
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(text);
        utter.lang = lang;
        window.speechSynthesis.speak(utter);
    }
};
