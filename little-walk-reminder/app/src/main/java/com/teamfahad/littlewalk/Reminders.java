package com.teamfahad.littlewalk;

import android.content.Context;
import androidx.work.ExistingPeriodicWorkPolicy;
import androidx.work.OneTimeWorkRequest;
import androidx.work.PeriodicWorkRequest;
import androidx.work.WorkManager;
import androidx.work.Data;
import java.util.concurrent.TimeUnit;

public final class Reminders {
    private static final String WORK_NAME = "little_walk_hourly";
    private static final String PREFS = "little_walk_preferences";
    private static final String ENABLED = "enabled";
    public static final String TEST = "is_test";
    public static final String WORDS = "It's time for a little walk. Thank you!";

    private Reminders() { }

    public static boolean isEnabled(Context context) {
        return context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
                .getBoolean(ENABLED, false);
    }

    public static void start(Context context) {
        PeriodicWorkRequest request = new PeriodicWorkRequest.Builder(
                WalkWorker.class, 1, TimeUnit.HOURS)
                .setInitialDelay(1, TimeUnit.HOURS)
                .build();
        WorkManager.getInstance(context).enqueueUniquePeriodicWork(
                WORK_NAME, ExistingPeriodicWorkPolicy.REPLACE, request);
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
                .edit().putBoolean(ENABLED, true).apply();
    }

    public static void stop(Context context) {
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
                .edit().putBoolean(ENABLED, false).apply();
        WorkManager.getInstance(context).cancelUniqueWork(WORK_NAME);
    }

    public static void test(Context context) {
        OneTimeWorkRequest request = new OneTimeWorkRequest.Builder(WalkWorker.class)
                .setInputData(new Data.Builder().putBoolean(TEST, true).build())
                .build();
        WorkManager.getInstance(context).enqueue(request);
    }
}
