# flutterapp

A Flutter UI for docket

## Not ready for use

The flutter client is an active playground for me to learn flutter. If I'm able
to learn enough flutter to make something useful, I'm aiming to build a feature
compatible mobile client as the responsive web client has some rough spots that
I'd like to improve on.

## Flutter Notes

### Installation

Flutter environment installation is pretty painful and on linux requires installing
flutter and dart via snapd, then installing android-studio from a zip file. You get no context on where it should be unpacked, so I dumped mine in ~/.local and work with it for now. Once you have that all working, you can then use android-studio to download and create device emulators. You also need to use android-studio to create the flutter project.

### Using vim, not android-studio

Part of my bad stubborn habits is not wanting to ever learn another IDE. Instead I would prefer a rougher but more consistent development experience with vim than to have to learn multiple sets of tools. This also costs me time by having to spend a few hours fiddling with vim plugins to get a decent workflow. I have to give a big shoutout to [akinsho for their flutter-tools](https://github.com/akinsho/flutter-tools.nvim) package. It seems really well thought out and designed. I really like how easy it is to start an emulator with `flutter emulators --launch` and then run an application with hot reloads from inside vim.

I also needed to install the `dart` language with `TSInstall dart` to get syntax highlighting and indenting.

#### LSP

I've not been able to get useful LSP output yet, but I'll have to figure that out next.



