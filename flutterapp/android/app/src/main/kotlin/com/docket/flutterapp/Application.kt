package com.docket.flutterapp

import io.flutter.app.FlutterApplication
import io.flutter.plugin.common.PluginRegistry
import io.flutter.plugin.common.PluginRegistry.PluginRegistrantCallback
import com.tekartik.sqflite.SqflitePlugin

class Application : FlutterApplication(), PluginRegistrantCallback {
    override fun onCreate() {
        super.onCreate()
    }

    override fun registerWith(registry: PluginRegistry) {
        SqflitePlugin.registerWith(registry?.registrarFor("com.tekartik.sqflite.SqflitePlugin"));
    }
}
