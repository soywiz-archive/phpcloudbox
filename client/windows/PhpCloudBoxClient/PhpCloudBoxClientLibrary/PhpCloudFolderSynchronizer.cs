using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading;
using System.Threading.Tasks;

namespace PhpCloudBoxClientLibrary
{
    public class PhpCloudFolderSynchronizer
    {
		FileSystemWatcher FileSystemWatcher;
		PhpCloudBoxServer Server;
		string RootPath;
		Dictionary<string, DateTime> Changes;
		Thread UpdateThread;

		public PhpCloudFolderSynchronizer(PhpCloudBoxServer Server, string RootPath)
		{
			if (!Directory.Exists(RootPath)) Directory.CreateDirectory(RootPath);
			RootPath = new DirectoryInfo(RootPath).FullName;

			FileSystemWatcher = new FileSystemWatcher(RootPath);
			FileSystemWatcher.IncludeSubdirectories = true;
			FileSystemWatcher.InternalBufferSize = 1 * 1024 * 1024;
			Console.WriteLine(FileSystemWatcher.InternalBufferSize);
			FileSystemWatcher.Changed += FileSystemWatcher_Changed;
			FileSystemWatcher.Renamed += FileSystemWatcher_Renamed;
			FileSystemWatcher.Created += FileSystemWatcher_Created;
			FileSystemWatcher.Deleted += FileSystemWatcher_Deleted;
			FileSystemWatcher.EnableRaisingEvents = true;
			Changes = new Dictionary<string, DateTime>();

			this.Server = Server;
			this.RootPath = RootPath;

			UpdateThread = new Thread(UpdateThreadMain);
			UpdateThread.IsBackground = true;
			UpdateThread.Start();
		}

		private void UpdateThreadMain()
		{
			while (true)
			{
				Thread.Sleep(TimeSpan.FromSeconds(0.25));
				
				var Now = DateTime.UtcNow;
				var Updated = new List<string>();
				
				lock (Changes)
				{
					foreach (var Pair in Changes) if (Pair.Value >= Now) Updated.Add(Pair.Key);
				}

				if (Updated.Count > 0)
				{
					Console.WriteLine("Tasks!");
					foreach (var RemoteFilePath in Updated)
					{
						var LocalFilePath = this.RootPath + "/" + RemoteFilePath;
						var Exists = File.Exists(LocalFilePath);
						Console.WriteLine("UpdateThreadMain: {0} :: {1} <-> {2}", RemoteFilePath, LocalFilePath, Exists);

						try
						{
							// Upload
							if (Exists)
							{
								Console.WriteLine("  Uploading...");
								Server.AddAndUploadFileAsync(RemoteFilePath, LocalFilePath).Wait();
								Console.WriteLine("  Done");
							}
							// Remove
							else
							{
								Console.WriteLine("  Deleting...");
								Server.DeleteFileAsync(RemoteFilePath).Wait();
								Console.WriteLine("  Done");
							}

							lock (Changes) Changes.Remove(RemoteFilePath);
						}
						catch (Exception Exception)
						{
							Console.Error.WriteLine(Exception);
						}

						Console.WriteLine("/UpdateThreadMain");
					}
				}
			}
		}

		async public Task InitialDownloadAsync()
		{
			FileSystemWatcher.EnableRaisingEvents = false;
			foreach (var FileInfo in await Server.GetFileListAsync())
			{
				Console.Write("{0}...", FileInfo.path);
				{
					// TODO SECURITY!: Fix if File.path contains ../
					var FullLocalPath = this.RootPath + "/" + FileInfo.path;
					var FullLocalPathDirectory = Path.GetDirectoryName(FullLocalPath);
					
					// Create Path if required
					if (!Directory.Exists(FullLocalPathDirectory)) Directory.CreateDirectory(FullLocalPathDirectory);
					
					// Download file if we don't have it yet.
					if (!File.Exists(FullLocalPath))
					{
						try
						{
							await Server.DownloadFileToAsync(FileInfo.path, FullLocalPath);
							Console.WriteLine("Ok");
						}
						catch (Exception Exception)
						{
							Console.WriteLine("Error");
							Console.Error.WriteLine(Exception);
						}
					}
					else
					{
						Console.WriteLine("Exists");
					}
				}
			}
			FileSystemWatcher.EnableRaisingEvents = true;
		}

		async public Task UploadUpdatedFilesAsync()
		{
			// TODO: ...
		}

		void UpdateFile(string FullPath)
		{
			var RelativePath = FullPath.Substring(RootPath.Length + 1).Replace('\\', '/');
			lock (Changes)
			{
				Changes[RelativePath] = DateTime.UtcNow + TimeSpan.FromSeconds(0.5);
			}
		}

		void FileSystemWatcher_Deleted(object sender, FileSystemEventArgs e)
		{
			Console.WriteLine("Deleted: {0}", e.FullPath);
			UpdateFile(e.FullPath);
		}

		void FileSystemWatcher_Created(object sender, FileSystemEventArgs e)
		{
			Console.WriteLine("Created: {0}", e.FullPath);
			UpdateFile(e.FullPath);
		}

		void FileSystemWatcher_Changed(object sender, FileSystemEventArgs e)
		{
			Console.WriteLine("Changed: {0}", e.FullPath);
			UpdateFile(e.FullPath);
		}

		void FileSystemWatcher_Renamed(object sender, RenamedEventArgs e)
		{
			Console.WriteLine("Renamed: {0} -> {1}", e.OldFullPath, e.FullPath);
			UpdateFile(e.OldFullPath);
			UpdateFile(e.FullPath);
		}
    }
}
