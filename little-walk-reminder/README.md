# Little Walk — Android reminder

A small, offline Android app that reminds you about every **60 minutes**:

> It's time for a little walk. Thank you!

It provides both an Android notification and **spoken English text-to-speech** (if the device allows background audio). Tap **Start hourly reminders** after installing, allow notification permission, and use **Test voice + notification** to check your phone's settings. Tap **Stop reminders** to cancel.

## Build

- Android Studio or GitHub Actions
- JDK 17, Android SDK Platform 35, Android Gradle Plugin 8.7.3, Gradle 8.9
- Build command: gradle :app:assembleDebug from this directory
- APK: app/build/outputs/apk/debug/app-debug.apk

The .github/workflows/build-walk-reminder.yml workflow automatically builds an APK artifact when changes are pushed to the dedicated branch.

## Notes

- WorkManager handles repeat scheduling across app restarts and device reboots.
- First reminder is **about one hour after enabling**; further reminders are approximately hourly.
- Android may delay timing to save battery; TTS voice may be blocked in the background by some devices.
- No internet permission, server, account, analytics, or data collection.
- The APK is a **debug APK** intended for personal installation.
