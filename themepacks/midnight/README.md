![Preview](https://user-images.githubusercontent.com/10499845/193457968-c16c8e55-e3f7-4360-b26b-4c054a2993be.png)

# Intro
**"Midnight"** is a design theme using darker shades of color similar to "Slate Blue" (according to X11 colors), designed to reduce eye pain when browsing at *midnight* or at all times.

The key feature of the design theme is to be **as close to the standard design theme as possible**, which is why you'll not see a badass (in a good way) design of that button which does not need to be styled.

# Contributing
If I ([Lumaeris](https://github.com/Lumaeris)) didn't manage to style a new feature in time or if you develop a new feature with styling yourself, please remember the key feature of the design theme.

> _"**as close to the standard design theme as possible**"_

What is meant here is that you should NOT add unnecessary properties (such as `width`, `overflow-x`, `cursor`, etc). You should only focus on color changes and only add critical properties where needed. That's it. *Keep It Simple, Silly.*

When upgrading a version, you don't have to choose something specific like `0.2.64.85` or `2038-1-19.1`, just increase the number in the last part of the version line (`0.0.5.6` -> `0.0.5.7`). It is necessary to update the version constantly to avoid forced caching of the style file. Increase the version is necessary not only in `theme.yml`, but also in `stylesheet.css` ("Replace All" will help in this).
