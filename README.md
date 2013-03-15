ManiaSpace
==========
This class will give you the ability to return basic information from the TrackMania Forever manialink [ManiaSpace](tmtp:///:maniaspace "tmtp:///:ManiaSpace").
You should consider caching the data locally instead of requesting it everytime a user reloads the site. This would decrease the number of cUrl requests.

Requirements
------------
- cUrl has to be enabled

Methods
-------
- **`getTrackIDs([$account])`**  
returns an Array containing the track ids of all uploaded tracks by the given TMF account. Is no account given, it will return the 15 latest track ids.
- **`getTracks([$account])`**  
returns an Array containing id, name, image url, ManiaSpace link and download link for each map uploaded by the given account. Is no account given, it will return the data of the 15 latest tracks.
- **`getTrackDetails($id)`**  
returns an Array containing name, author, imageurl, download link, ManiaSpace link, download count, environment, mood, type, laps, mod, comment, times, first 7 replays
