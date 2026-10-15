package com.teamfahad.littlewalk;

import android.Manifest;
import android.app.Activity;
import android.content.pm.PackageManager;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.os.Build;
import android.os.Bundle;
import android.view.Gravity;
import android.view.View;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

public class MainActivity extends Activity {
    private static final int REQUEST_NOTIFICATIONS = 110;
    private static final int BACKGROUND = Color.rgb(16, 25, 35);
    private static final int PANEL = Color.rgb(25, 39, 48);
    private static final int MINT = Color.rgb(162, 234, 199);
    private static final int MUTED = Color.rgb(169, 187, 192);
    private LinearLayout root;
    private TextView status;
    private Button toggle;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        createUi();
        refresh();
    }

    private void createUi() {
        ScrollView scroll = new ScrollView(this);
        scroll.setFillViewport(true);
        scroll.setBackgroundColor(BACKGROUND);
        root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setPadding(dp(28), dp(44), dp(28), dp(32));
        scroll.addView(root);
        setContentView(scroll);

        TextView brand = text("●   LITTLE WALK", 15, MINT, Typeface.BOLD);
        root.addView(brand);
        spacer(66);

        TextView title = text("A small walk.\nA brighter day.", 38, Color.WHITE, Typeface.BOLD);
        title.setLineSpacing(dp(4), 1f);
        root.addView(title);
        spacer(16);

        TextView body = text("A gentle reminder to stand up, stretch your legs and move for a moment.",
                17, MUTED, Typeface.NORMAL);
        body.setLineSpacing(dp(4), 1f);
        root.addView(body);
        spacer(45);

        LinearLayout card = new LinearLayout(this);
        card.setOrientation(LinearLayout.VERTICAL);
        card.setPadding(dp(24), dp(26), dp(24), dp(26));
        card.setBackground(roundRect(PANEL, 23));
        root.addView(card, new LinearLayout.LayoutParams(-1, -2));

        TextView cadence = text("EVERY 60 MINUTES", 12, MINT, Typeface.BOLD);
        card.addView(cadence);
        card.addView(spaceView(14));
        TextView quote = text("“It's time for a little walk. Thank you!”", 23,
                Color.WHITE, Typeface.NORMAL);
        quote.setLineSpacing(dp(4), 1f);
        card.addView(quote);
        card.addView(spaceView(20));
        status = text("", 14, MUTED, Typeface.NORMAL);
        card.addView(status);
        spacer(30);

        toggle = new Button(this);
        toggle.setAllCaps(false);
        toggle.setTextSize(16);
        toggle.setTypeface(Typeface.DEFAULT, Typeface.BOLD);
        toggle.setTextColor(BACKGROUND);
        toggle.setBackground(roundRect(MINT, 15));
        toggle.setPadding(dp(16), dp(12), dp(16), dp(12));
        root.addView(toggle, new LinearLayout.LayoutParams(-1, dp(60)));
        toggle.setOnClickListener(v -> {
            if (Reminders.isEnabled(this)) {
                Reminders.stop(this);
                refresh();
            } else {
                askAndStart();
            }
        });
        spacer(14);

        Button test = new Button(this);
        test.setText("Test voice + notification");
        test.setAllCaps(false);
        test.setTextSize(15);
        test.setTextColor(Color.WHITE);
        test.setBackground(roundRect(PANEL, 15));
        root.addView(test, new LinearLayout.LayoutParams(-1, dp(56)));
        test.setOnClickListener(v -> {
            if (!hasNotificationPermission()) {
                requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS},
                        REQUEST_NOTIFICATIONS);
                Toast.makeText(this, "Allow notifications, then tap Test again", Toast.LENGTH_LONG).show();
                return;
            }
            Reminders.test(this);
            Toast.makeText(this, "Test reminder queued", Toast.LENGTH_SHORT).show();
        });
        spacer(30);
        TextView footer = text("Works offline · Uses your phone's text-to-speech voice. " +
                        "Android may delay reminders to save battery.",
                13, MUTED, Typeface.NORMAL);
        footer.setLineSpacing(dp(3), 1f);
        footer.setGravity(Gravity.CENTER);
        root.addView(footer);
    }

    private void askAndStart() {
        if (!hasNotificationPermission()) {
            requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS},
                    REQUEST_NOTIFICATIONS);
        } else {
            Reminders.start(this);
            refresh();
            Toast.makeText(this, "First reminder in about 1 hour", Toast.LENGTH_LONG).show();
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions,
                                           int[] results) {
        super.onRequestPermissionsResult(requestCode, permissions, results);
        if (requestCode == REQUEST_NOTIFICATIONS && results.length > 0 &&
                results[0] == PackageManager.PERMISSION_GRANTED) {
            Reminders.start(this);
            refresh();
            Toast.makeText(this, "Hourly walk reminders enabled", Toast.LENGTH_SHORT).show();
        } else if (requestCode == REQUEST_NOTIFICATIONS) {
            Toast.makeText(this, "Notification permission is required", Toast.LENGTH_LONG).show();
        }
    }

    private boolean hasNotificationPermission() {
        return Build.VERSION.SDK_INT < 33 || checkSelfPermission(
                Manifest.permission.POST_NOTIFICATIONS) == PackageManager.PERMISSION_GRANTED;
    }

    private void refresh() {
        boolean enabled = Reminders.isEnabled(this);
        status.setText(enabled ? "●  REMINDERS ON  ·  Voice + notification" :
                "○  REMINDERS OFF");
        status.setTextColor(enabled ? MINT : MUTED);
        toggle.setText(enabled ? "Stop reminders" : "Start hourly reminders");
    }

    private TextView text(String value, int sp, int color, int style) {
        TextView t = new TextView(this);
        t.setText(value);
        t.setTextSize(sp);
        t.setTextColor(color);
        t.setTypeface(Typeface.create("sans-serif", style));
        return t;
    }

    private GradientDrawable roundRect(int color, int radiusDp) {
        GradientDrawable d = new GradientDrawable();
        d.setColor(color);
        d.setCornerRadius(dp(radiusDp));
        return d;
    }

    private int dp(int value) {
        return Math.round(value * getResources().getDisplayMetrics().density);
    }

    private void spacer(int dp) { root.addView(spaceView(dp)); }
    private View spaceView(int heightDp) {
        View v = new View(this);
        v.setLayoutParams(new LinearLayout.LayoutParams(1, dp(heightDp)));
        return v;
    }
}
