# flutterapp

A Flutter UI for docket

## Not ready for use

The flutter client is an active playground for me to learn flutter. If I'm able
to learn enough flutter to make something useful, I'm aiming to build a feature
compatible mobile client as the responsive web client has some rough spots that
I'd like to improve on.

Currently, the flutter client can be used to do most day to day tasks. It isn't
fully tested, but I'm trying to use it as my daily driver now to iterate and
improve on the flows.

## Flutter Notes

### Flutter Installation

Flutter environment installation is pretty painful and on linux requires installing
flutter and dart via snapd, then installing android-studio from a zip file. You get no context on where it should be unpacked, so I dumped mine in ~/.local and work with it for now. Once you have that all working, you can then use android-studio to download and create device emulators. You also need to use android-studio to create a project so that you can a virutal device.

### Using vim, not android-studio

Part of my bad stubborn habits is not wanting to ever learn another IDE. Instead I would prefer a rougher but more consistent development experience with vim than to have to learn multiple sets of tools. This also costs me time by having to spend a few hours fiddling with vim plugins to get a decent workflow. I have to give a big shoutout to [akinsho for their flutter-tools](https://github.com/akinsho/flutter-tools.nvim) package. It seems really well thought out and designed. I really like how easy it is to start an emulator with `flutter emulators --launch` and then run an application with hot reloads from inside vim.

I also needed to install the `dart` language with `TSInstall dart` to get syntax highlighting and indenting.

#### LSP

I got LSP working with akinsho's plugin. I also had to upgrade my completion menu vim plugin to [nvim-cmp](https://github.com/hrsh7th/nvim-cmp). This process took some time and I had to disable automatic autocompletion as it was causing segfaults in my flutter project. I think this is probably something I should have done a long time ago as I often battle with the visual noise and overlap that the autocomplete menu uses.

### Build and Install

Once you've configured your phone to accept unsigned binaries over USB, then run:

```bash
cd flutterapp
flutter pub get
flutter build apk
flutter install
```

This will refresh dependencies build an APK and install an APK onto your device.
By default the android application is configured to use
`https://docket.mark-story.com`. If you want to point it at your instance of
docket, replace all instances of that domain with yours and then `flutter build
apk && flutter install`.
