using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;
using PhpCloudBoxClientLibrary;

namespace PhpCloudBoxClient
{
	static class Program
	{
		/// <summary>
		/// The main entry point for the application.
		/// </summary>
		[STAThread]
		static void Main()
		{
			//Application.EnableVisualStyles();
			//Application.SetCompatibleTextRenderingDefault(false);
			//Application.Run(new Form1());

			Task.Run(async () =>
			{
				var Server = new PhpCloudBoxServer("http://127.0.0.1:9999", "test", "test");
				var Synchronizer = new PhpCloudFolderSynchronizer(Server, @"c:\temp\cloudbox");
				await Synchronizer.InitialDownloadAsync();

				/*
				Console.WriteLine(String.Join("\n", (await Server.GetFileListAsync()).Select(Item => Item.path)));
				//await Server.DownloadFileAsync("test2.png");
				//await Server.DownloadFileToAsync("test2.png", @"c:\temp\lol.png");
				await Server.DeleteFileAsync("test.png");
				await Server.AddAndUploadFileAsync("temp.bmp", @"c:\temp\temp.bmp");
				*/

				Console.ReadKey();
			}).Wait();
		}
	}
}
