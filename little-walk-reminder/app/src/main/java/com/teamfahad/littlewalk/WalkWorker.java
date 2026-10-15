package com.teamfahad.littlewalk;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import androidx.annotation.NonNull;
import androidx.work.Worker;
import androidx.work.WorkerParameters;

public class WalkWorker extends Worker {
    private static final String CHANNEL = "little_walk_notifications";

    public WalkWorker(@NonNull Context context, @NonNull WorkerParameters params) {
        super(context, params);
    }

    @NonNull
    @Override
    public Result doWork() {
        Context context = getApplicationContext();
        boolean isTest = getInputData().getBoolean(Reminders.TEST, false);
        if (!isTest && !Reminders.isEnabled(context)) {
            return Result.success();
        }
        sendNotification(context);
        Speech.say(context, Reminders.WORDS);
        return Result.success();
    }

    private static void sendNotification(Context context) {
        NotificationManager manager = context.getSystemService(NotificationManager.class);
        if (manager == null) return;
        NotificationChannel channel = new NotificationChannel(CHANNEL,
                "Walk reminders", NotificationManager.IMPORTANCE_DEFAULT);
        channel.setDescription("A little walk, once an hour");
        manager.createNotificationChannel(channel);

        Intent intent = new Intent(context, MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
        PendingIntent pendingIntent = PendingIntent.getActivity(context, 0, intent,
                PendingIntent.FLAG_IMMUTABLE | PendingIntent.FLAG_UPDATE_CURRENT);

        Notification notification = new Notification.Builder(context, CHANNEL)
                .setSmallIcon(R.drawable.ic_walk)
                .setContentTitle("Time for a little walk")
                .setContentText(Reminders.WORDS)
                .setStyle(new Notification.BigTextStyle().bigText(Reminders.WORDS))
                .setContentIntent(pendingIntent)
                .setAutoCancel(true)
                .build();
        try {
            manager.notify(1001, notification);
        } catch (SecurityException ignored) {
            // On Android 13+, the user may decline notification permission.
            // Speech can still run when the system permits background audio.
        }
    }
}
