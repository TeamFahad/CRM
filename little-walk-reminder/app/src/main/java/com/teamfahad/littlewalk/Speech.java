package com.teamfahad.littlewalk;

import android.content.Context;
import android.media.AudioAttributes;
import android.speech.tts.TextToSpeech;
import android.speech.tts.UtteranceProgressListener;
import java.util.Locale;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicInteger;

public final class Speech {
    private Speech() { }

    public static void say(Context context, String message) {
        CountDownLatch initialized = new CountDownLatch(1);
        AtomicInteger status = new AtomicInteger(TextToSpeech.ERROR);
        TextToSpeech tts = new TextToSpeech(context.getApplicationContext(), result -> {
            status.set(result);
            initialized.countDown();
        });
        try {
            if (!initialized.await(12, TimeUnit.SECONDS)
                    || status.get() != TextToSpeech.SUCCESS) return;
            tts.setLanguage(Locale.US);
            tts.setSpeechRate(0.9f);
            tts.setAudioAttributes(new AudioAttributes.Builder()
                    .setUsage(AudioAttributes.USAGE_NOTIFICATION_EVENT)
                    .setContentType(AudioAttributes.CONTENT_TYPE_SPEECH)
                    .build());
            CountDownLatch finished = new CountDownLatch(1);
            tts.setOnUtteranceProgressListener(new UtteranceProgressListener() {
                @Override public void onStart(String id) { }
                @Override public void onDone(String id) { finished.countDown(); }
                @Override public void onError(String id) { finished.countDown(); }
            });
            if (tts.speak(message, TextToSpeech.QUEUE_FLUSH, null, "little_walk_speech")
                    == TextToSpeech.SUCCESS) {
                finished.await(22, TimeUnit.SECONDS);
            }
        } catch (InterruptedException e) {
            Thread.currentThread().interrupt();
        } finally {
            tts.stop();
            tts.shutdown();
        }
    }
}
